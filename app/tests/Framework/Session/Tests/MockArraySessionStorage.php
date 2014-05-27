<?php namespace Framework\Session\Tests;

use Framework\Session\SessionStorageInterface;

/**
 * MockArraySessionStorage mocks the session for unit tests.
 *
 * No PHP session is actually started since a session can be initialized
 * and shutdown only once per PHP execution cycle.
 *
 */
class MockArraySessionStorage implements SessionStorageInterface
{
    /**
     * @var string
     */
    protected $id = '';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var boolean
     */
    protected $started = false;

    /**
     * @var boolean
     */
    protected $closed = false;

    /**
     * @var array
     */
    protected $data = array();


    /**
     * Constructor.
     *
     * @param string      $name    Session name
     * @param MetadataBag $metaBag MetadataBag instance.
     */
    public function __construct($name = 'MOCKSESSID')
    {
        $this->name = $name;
    }

    /**
     * Sets the session data.
     *
     * @param array $array
     */
    public function setSessionData(array $array)
    {
        $this->data = $array;
    }

    public function set(array $array)
    {
        $key = key(array_slice($array, 0, 1, true));
        $this->data[$key] = $array[$key];
    }

    public function get($name,$default)
    {
        return isset($this->data[$name])?$this->data[$name]:$default;
    }

    public function has($name)
    {
        return isset($this->data[$name]);
    }

    public function all()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        if ($this->started && !$this->closed) {
            return true;
        }

        if (empty($this->id)) {
            $this->id = $this->generateId();
        }

        $this->loadSession();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function regenerate($destroy = false, $lifetime = null)
    {
        if (!$this->started) {
            $this->start();
        }

        $this->metadataBag->stampNew($lifetime);
        $this->id = $this->generateId();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        if ($this->started) {
            throw new \LogicException('Cannot set session ID after the session has started.');
        }

        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        if (!$this->started || $this->closed) {
            throw new \RuntimeException("Trying to save a session that was not started yet or was already closed");
        }
        // nothing to do since we don't persist the session data
        $this->closed = false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        // clear out the bags
        foreach ($this->bags as $bag) {
            $bag->clear();
        }

        // clear out the session
        $this->data = array();

        // reconnect the bags to the session
        $this->loadSession();
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Generates a session ID.
     *
     * This doesn't need to be particularly cryptographically secure since this is just
     * a mock.
     *
     * @return string
     */
    protected function generateId()
    {
        return sha1(uniqid(mt_rand()));
    }

    protected function loadSession()
    {

        $this->started = true;
        $this->closed = false;
    }
}
