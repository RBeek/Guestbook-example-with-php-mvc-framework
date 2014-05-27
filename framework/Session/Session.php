<?php namespace Framework\Session;

use Framework\Support\Helpers as h;

/**
 * Class and Function List:
 * Function list:
 * - isStarted()
 * - clear()
 * - replace()
 * - migrate()
 * - invalidate()
 * - setName()
 * - getName()
 * - setId()
 * - getId()
 * - __construct()
 * - start()
 * - read()
 * - write()
 * - create()
 * - update()
 * - destroy()
 * - get()
 * - all()
 * - has()
 * - set()
 * - remove()
 * - flashAdd()
 * - flashKeep()
 * - flashGet()
 * - flashMark()
 * - flashSweep()
 * - getTime()
 * - _setCookie()
 * - _serialize()
 * - _unserialize()
 * - _sessGc()
 * - strip_slashes()
 * Classes list:
 * - Session
 */

class Session implements SessionInterface
{

    private $encrypt_cookie = FALSE;
    private $use_db = FALSE;
    private $table = 'ams_sessions';
    private $expire = 60;
    private $match_ip = FALSE;
    private $match_useragent = TRUE;
    private $cookie_name = 'ams_session';
    private $cookie_prefix = '';
    private $cookie_path = '';
    private $cookie_domain = '';
    private $update = 300;
    private $encrypt_key = '';
    private $flash_key = 'flash';
    private $time_reference = 'time';
    private $gc_probability = 5;
    private $userdata = array();
    private $now;
    private $storage_default = "native";
    protected $started = false;
    protected $closed = false;
    protected $storage = false;
    private $dba = null;

    public function isStarted(){return $this->storage!=="native"?$this->storage->isStarted():$this->started;}
    public function clear(){}
    public function replace(array $attributes){}
    public function migrate($destroy = false, $lifetime = null){}
    public function invalidate($lifetime = null){}
    public function setName($name){}
    public function getName(){}
    public function setId($id){}
    public function getId(){
        if($this->storage == $this->storage_default){
            if (!$this->started) {
                return '';
            }
            return session_id();
        }else{
          return  $this->storage->getId();
        }
    }

    public function __construct($storage = null, $config = array())
    {
        session_cache_limiter('');

        // disable by default because it's managed by HeaderBag (if used)
        ini_set('session.use_cookies', 1);

        if (version_compare(phpversion(), '5.4.0', '>=')) {
            session_register_shutdown();
        } else {
            register_shutdown_function('session_write_close');
        }

        $this->storage = $storage?: $this->storage_default;

        foreach ($config as $key => $val) {
            if (isset($this->$key)) $this->$key = $val;
        }

        if ($this->use_db == TRUE and $this->table != '') {
            $this->dba = new Dba(Db::getInstance());
        }

        // using it to track last activity
        $this->now = $this->getTime();

        //session expire 0 == 2 year.
        $this->expire = $this->expire ? : (60 * 60 * 24 * 365 * 2);

        // cookie name
        $this->cookie_name = $this->cookie_prefix . $this->cookie_name;
    }

    public function start()
    {
        if ($this->started && !$this->closed) {
            return true;
        }

        if($this->storage == "native"){
            if (version_compare(phpversion(), '5.4.0', '>=') && \PHP_SESSION_ACTIVE === session_status()) {
                throw new \RuntimeException('Failed to start the session: already started by PHP.');
            }

            if (version_compare(phpversion(), '5.4.0', '<') && isset($_SESSION) && session_id()) {

                // not 100% fool-proof, but is the most reliable way to determine if a session is active in PHP 5.3
                throw new \RuntimeException('Failed to start the session: already started by PHP ($_SESSION is set).');
            }

            if (ini_get('session.use_cookies') && headers_sent($file, $line)) {
                throw new \RuntimeException(sprintf('Failed to start the session because headers have already been sent by "%s" at line %d.', $file, $line));
            }

            // ok to try and start the session
            if (!session_start()) {
                throw new \RuntimeException('Failed to start the session');
            }

            // Execute session routine..
            if (!$this->read()) {
                $this->create();
            } else {
                $this->update();
            }

            // Remove 'old' flashdata (last request)
            $this->flashSweep();

            // Mark new flashdata as old ( data will be removed 'before' the next request)
            $this->flashMark();

            // Remove expired sessions
            $this->_sessGc();

        }else{
            $this->storage->start();
        }

        $this->started = true;
        $this->closed = false;
        return true;
    }

    /**
     * Get current session data
     *
     * @access  public
     * @return  bool
     */
    public function read()
    {

        $session = h::cookie($this->cookie_name);

        if ($session == FALSE) return FALSE;

        // Decode encrypted cookie
        if ($this->encrypt_cookie == TRUE) {
        } else {

            // Encryption not used, verify md5 hash
            $session = urldecode($session);
            $hash = substr($session, strlen($session) - 32);

            // Get last 32 karakters ( second part)
            $session = substr($session, 0, strlen($session) - 32);

            // first part

            // Is it a match?
            if ($hash !== md5($session . $this->encrypt_key)) {

                // possible hack
                $this->destroy();
                return FALSE;
            }
        }

        // Unserialize the session array
        $session = $this->_unserialize($session);

        // Do they have the right markup?
        if (!is_array($session) or !isset($session['session_id']) or !isset($session['ip_address']) or !isset($session['user_agent']) or !isset($session['last_activity'])) {
            $this->destroy();
            return FALSE;
        }

        // Session still valid?
        if (($session['last_activity'] + $this->expire) < $this->now) {

            $this->destroy();
            return FALSE;
        }

        // IP a Match?
        if ($this->match_ip == TRUE and $session['ip_address'] != h::ip_address()) {
            $this->destroy();
            return FALSE;
        }

        // User Agent a Match?
        if ($this->match_useragent == TRUE and trim($session['user_agent']) != trim(substr(h::user_agent(), 0, 50))) {
            $this->destroy();
            return FALSE;
        }

        // Corresponding DB session?
        if ($this->use_db == TRUE) {
            $this->dba->select($this->table);
            $this->dba->voorvalue('session_id', $session['session_id']);

            if ($this->match_ip == TRUE) {
                $this->dba->and_condition('ip_address', $session['ip_address']);
            }

            if ($this->match_useragent == TRUE) {
                $this->dba->and_condition('user_agent', $session['user_agent']);
            }

            // NO result?  Kill it!
            if ($this->dba->queryCount($this->table) == 0) {
                $this->destroy();
                return FALSE;
            }

            // Changed data? YES, add it to the main session
            $row = $this->dba->query(true);

            if (isset($row['userdata']) AND $row['userdata'] != '') {
                $changed_data = $this->_unserialize($row['userdata']);

                if (is_array($changed_data)) {
                    foreach ($changed_data as $k => $v) {
                        $session[$k] = $v;
                    }
                }
            }
        }

        // session is valid!
        $this->userdata = $session;
        unset($session);

        return TRUE;
    }

    /**
     * Write session data
     *
     * @access  public
     * @return  void
     */
    public function write()
    {

        // Store changed_data in DB?  NO, then only a cookie update
        if ($this->use_db == FALSE) {
            $this->_setCookie();
            return;
        }

        // set changed userdata
        $changed_userdata = $this->userdata;
        $cookie_userdata = array();

        // Determine if there is changed data.
        // Remove default indexes to see if something is left.
        // At the same time set session data.
        foreach (array('session_id', 'ip_address', 'user_agent', 'last_activity') as $value) {
            unset($changed_userdata[$value]);
            $cookie_userdata[$value] = $this->userdata[$value];
        }

        // NO changed data?  Set empty array to a string, no reason to serialize
        if (count($changed_userdata) === 0) {
            $changed_userdata = '';
        } else {

            // Serialize changed data array for storage
            $changed_userdata = $this->_serialize($changed_userdata);
        }

        // Execute update query
        $return = $this->dba->update($this->table, array('session_id', $this->userdata['session_id']), array('last_activity' => $this->userdata['last_activity'], 'userdata' => $changed_userdata));

        // Create a cookie. Normally $this->userdata will be used, but
        // in this case we have changed data , we do not want to add to a cookie.
        $this->_setCookie($cookie_userdata);
    }

    /**
     * Create session
     *
     * @access  public
     * @return  void
     */
    public function create()
    {
        $sessid = '';
        while (strlen($sessid) < 32) {
            $sessid.= mt_rand(0, mt_getrandmax());
        }

        // Combine session ID with IP address user
        $sessid.= h::ip_address();

        $this->userdata = array('session_id' => md5(uniqid($sessid, TRUE)), 'ip_address' => h::ip_address(), 'user_agent' => substr(h::user_agent(), 0, 50), 'last_activity' => $this->now);

        if ($this->use_db == TRUE) {
            $this->dba->add($this->table, $this->userdata);
        }

        // Create cookie
        $this->_setCookie();
    }

    /**
     * Update existing session
     *
     * @access  public
     * @return  void
     */
    public function update()
    {

        // Update session standard every five minutes
        if (($this->userdata['last_activity'] + $this->update) >= $this->now) {
            return;
        }

        // Store old session id so we know wich row to update
        // in the database if needed.
        $old_sessid = $this->userdata['session_id'];
        $new_sessid = '';
        while (strlen($new_sessid) < 32) {
            $new_sessid.= mt_rand(0, mt_getrandmax());
        }

        // Combine session ID with users IP address
        $new_sessid.= h::ip_address();

        // Change it into a hash
        $new_sessid = md5(uniqid($new_sessid, TRUE));

        // Update session data in data array
        $this->userdata['session_id'] = $new_sessid;
        $this->userdata['last_activity'] = $this->now;

        $cookie_data = NULL;

        // Update DB session ID and last_activity if needed
        if ($this->use_db == TRUE) {

            // set cookie
            $cookie_data = array();
            foreach (array('session_id', 'ip_address', 'user_agent', 'last_activity') as $value) {
                $cookie_data[$value] = $this->userdata[$value];
            }
            $this->dba->update($this->table, array('session_id', $old_sessid), array('last_activity' => $this->now, 'session_id' => $new_sessid));
        }

        // Create cookie
        $this->_setCookie($cookie_data);
    }

    /**
     * Destroy current session
     *
     * @access  public
     * @return  void
     */
    public function destroy()
    {

        // Destroy DB session
        if ($this->use_db == TRUE AND isset($this->userdata['session_id'])) {
            $this->dba->remove($this->table, $this->userdata['session_id'], 'session_id');
        }

        // Destroy cookie
        setcookie($this->cookie_name, addslashes(serialize(array())), ($this->now - 31500000), $this->cookie_path, $this->cookie_domain, 0);
    }

    public function get($name, $default = null)
    {
        if($this->storage !== $this->storage_default) return $this->storage->get($name, $default);
        return $value = isset($this->userdata[$name]) ? $this->userdata[$name]:$default;
    }

    public function all()
    {
        if($this->storage !== $this->storage_default) return $this->storage->all();
        return (!isset($this->userdata)) ? FALSE : $this->userdata;
    }

    public function has($name)
    {
        if($this->storage !== $this->storage_default) return $this->storage->has($name);
        return isset($this->userdata[$name]);
    }

    public function set($data = array(), $value = '')
    {

        if (is_string($data)) {
            $data = array($data => $value);
        }

        if($this->storage !== $this->storage_default){

            $this->storage->set($data, $value);
        }else{

            if (count($data) > 0) $this->userdata = $data;

            $this->write();
        }
    }

    public function remove($data = array())
    {
        if (is_string($data)) {
            $data = array($data => '');
        }

        if (count($data) > 0) {
            foreach ($data as $k => $v) {
                unset($this->userdata[$k]);
            }
        }

        $this->write();
    }

    public function flashAdd($data = array(), $value = '')
    {
        if (is_string($data)) {
            $data = array($data => $value);
        }

        if (count($data) > 0) {
            foreach ($data as $k => $v) {
                $flash_key = $this->flash_key . ':new:' . $k;
                $this->set($flash_key, $v);
            }
        }
    }

    public function flashKeep($key)
    {

        // 'old' flashdata will be removed. We mark
        // flashdata as 'new' to protect against flashdata_sweep()
        // Will return FALSE if key is not found.
        $flash_key_oud = $this->flash_key . ':old:' . $key;
        $value = $this->userdata($flash_key_oud);

        $flash_key_new = $this->flash_key . ':new:' . $key;
        $this->set($flash_key_new, $value);
    }

    public function flashGet($key, $message = '')
    {
        $flash_key = $this->flash_key . ':old:' . $key;
        return $this->userdata($flash_key) ? : $message;
    }

    /**
     * Marks data as 'old' before flashSweep() will be executed.
     *
     * @access  private
     * @return  void
     */
    private function flashMark()
    {
        $userdata = $this->all();
        foreach ($userdata as $name => $value) {
            $parts = explode(':new:', $name);
            if (is_array($parts) && count($parts) === 2) {
                $name_new = $this->flash_key . ':old:' . $parts[1];
                $this->set($name_new, $value);
                $this->unset_userdata($name);
            }
        }
    }

    /**
     * removes all flashdata marked as 'old'
     *
     * @access  private
     * @return  void
     */

    private function flashSweep()
    {
        $userdata = $this->all();
        foreach ($userdata as $key => $value) {
            if (strpos($key, ':old:')) {
                $this->unset_userdata($key);
            }
        }
    }

    private function getTime()
    {
        if (strtolower($this->time_reference) == 'gmt') {
            $now = time();
            $time = mktime(gmdate("H", $now), gmdate("i", $now), gmdate("s", $now), gmdate("m", $now), gmdate("d", $now), gmdate("Y", $now));
        } else {
            $time = time();
        }

        return $time;
    }

    /**
     * Create session cookie
     *
     * @access  public
     * @return  void
     */
    function _setCookie($cookie_data = NULL)
    {
        if (is_null($cookie_data)) {
            $cookie_data = $this->userdata;
        }

        // Serializeer de userdata voor de cookie
        $cookie_data = $this->_serialize($cookie_data);

        if ($this->encrypt_cookie == TRUE) {
        } else {
            $cookie_data = $cookie_data . md5($cookie_data . $this->encrypt_key);
        }

        $cookie_data = urlencode($cookie_data);

        setcookie($this->cookie_name, $cookie_data, time() + $this->expire, $this->cookie_path, $this->cookie_domain, 0);
    }

    /**
     * Serialize an array
     *
     *
     * @access  private
     * @param array
     * @return  string
     */
    private function _serialize($data)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = str_replace('\\', '{{slash}}', $v);
            }
        } else {
            $data = str_replace('\\', '{{slash}}', $data);
        }

        return serialize($data);
    }

    /**
     * Unserialize
     *
     *
     * @access  private
     * @param array
     * @return  string
     */
    private function _unserialize($data)
    {
        $data = @unserialize($this->strip_slashes($data));

        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = str_replace('{{slash}}', '\\', $v);
            }

            return $data;
        }

        return str_replace('{{slash}}', '\\', $data);
    }

    /**
     * Garbage collection
     *
     *
     * @access  public
     * @return  void
     */
    function _sessGc()
    {
        if ($this->use_db != TRUE) {
            return;
        }

        srand(time());
        if ((rand() % 100) < $this->gc_probability) {
            $expired = $this->now - $this->expire;

            $this->dba->select($this->table) . condition('last_activity', $expired, '<');
            foreach ($this->dba->query() as $row) {
                $this->dba->remove($this->table, $row['session_id'], 'session_id');
            }
        }
    }

    function strip_slashes($str)
    {
        if (is_array($str)) {
            foreach ($str as $k => $v) {
                $str[$k] = $this->strip_slashes($v);
            }
        } else {
            $str = stripslashes($str);
        }

        return $str;
    }
}
