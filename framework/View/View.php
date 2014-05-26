<?php namespace Framework\View;

/**
* Class and Function List:
* Function list:
* - __set()
* - __construct()
* - getVars()
* - render()
* - fetch()
* - getOutput()
* - setTemplateDir()
* - setCacheDir()
* - setCacheLifetime()
* - setCaching()
* - isCached()
* - addCache()
* - getCache()
* - clearCache()
* - delDir()
* Classes list:
* - View
*/


class View
{

    /**
     * De variables to be used in the templates.
     *
     * @access private
     * @var array
     */
    private $variables = array();

    private $template_dir = null;

    /**
     * Turn caching on/-of
     *
     * @access private
     * @var bool
     */
    private $caching = false;

    private $cache_dir = '/storage/cache';

    private $cache_lifetime = 300;

    public function __set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    public function __construct()
    {

        // parent::__construct(array(), ArrayObject::ARRAY_AS_PROPS);
        $this->setCacheDir(APP_PATH . $this->cache_dir);
    }

    /**
     * @Returns the names of all added variables
     *
     * Returns numeric array
     *
     * @access public
     *
     * @return array
     *
     */
    public function getVars()
    {
        $variables = array_keys($this->variables);
        return !empty($variables) ? $variables : false;
    }

    /**
     *
     * Outputs the finale template output
     *
     * Retrieves template output and echo's it to the browser.
     *
     * @param string $file filesname (path included)
     *
     * @param string $id Cache file identification number/string for retrieval
     *
     * @access public
     *
     * @return void
     *
     */
    public function render($file, $id = null)
    {
        echo $this->fetch($file, $id);
    }

    /**
     * Get finale template output
     *
     * @param string $template_file filename (path included)
     *
     * @param string $id Cache identification for file retrieval
     *
     * @access private
     *
     * @return string Returns  string on succes or FALSE
     *
     *
     */
    public function fetch($template_file, $id = null)
    {
        if (!empty($this->template_dir)) {
            $template_file = realpath($this->template_dir) . '/' . $template_file;
        }

        if ($this->caching == true && $this->isCached($template_file, $id)) {
            $output = $this->getCache($template_file, $id);
        } else {
            $output = $this->getOutput($template_file);

            if ($this->caching == true) {
                $this->addCache($output, $template_file, $id);
            }
        }
        return isset($output) ? $output : false;
    }

    /**
     *
     * Get template output
     *
     * @param string $template_file filename (path included) to be processed
     *
     * @return string Returns string on success, or FALSE
     *
     * @access private
     *   *
     */
    private function getOutput($template_file)
    {
        extract($this->variables);

        if (file_exists($template_file)) {
            ob_start();
            include ($template_file);
            $output = ob_get_contents();
            ob_end_clean();
        } else {
            throw new \Exception("De template '$template_file' bestaat niet");
        }
        return !empty($output) ? $output : false;
    }

    /**
     *
     *
     * @param string $dir Path to directory
     *
     * @access public
     *
     * @return void
     *
     */
    public function setTemplateDir($dir)
    {
        $template_dir = realpath($dir);
        if (is_dir($template_dir)) {
            $this->template_dir = $template_dir;
        } else {
            throw new \Exception("De folder '$dir' bestaat niet", 200);
        }
    }

    /**
     *
     * @param string $dir Path to cache directory
     *
     * @access public
     *
     * @return void
     *
     *
     */
    public function setCacheDir($cacheDir = null)
    {
        if (is_null($cacheDir)) {

            // $config = \config::getInstance();
            // $cacheDir = $config->config_values['template']['cache_dir'];

            $cacheDir = $this->cache_dir;
        }

        if (is_dir($cacheDir))

        // && is_writable($cacheDir)
        {

            // $config = \config::getInstance();
            $this->cache_dir = $cacheDir;
        } else {
            throw new \Exception("De cache '$cacheDir' ofwel bestaat niet, of er kan niet naartoe geschreven worden");
        }
    }

    /**
     *
     * @param INT $seconds
     *
     * @access public
     *
     * @return void
     *   *
     */
    public function setCacheLifetime($seconds = 0)
    {
        $this->cache_lifetime = is_numeric($seconds) ? $seconds : 0;
    }

    /**
     * Turn caching on/- off
     *
     * @param bool $status TRUE or FALSE
     *
     * @access public
     *
     * @return void
     *
     *
     */
    public function setCaching($status = false)
    {
        $this->caching = $status;
    }

    /**
     *
     * @param string $file filesname template
     *
     * @param string $id Cache file identification number/string for retrieval
     *
     * @access public
     *
     * @return bool
     *
     *
     */
    public function isCached($file, $id = null)
    {
        $cacheId = $id ? md5($id . basename($file)) : md5(basename($file));
        $bstname = $this->cache_dir . '/' . $cacheId . '/' . basename($file);
        if (is_file($bstname)) {
            clearstatcache();
            if (filemtime($bstname) > (time() - $this->cache_lifetime)) {
                $isCached = true;
            }
        }
        return isset($isCached) ? true : false;
    }

    /**
     * Creates cache file. Internal method
     *
     * @param string $content De template output to be cached
     *
     * @param string $file Filename template to be cached
     *
     * @param string $id Cache file identification number/string for retrieval
     *
     * @access private
     *
     * @return void
     *
     */
    private function addCache($content, $file, $id = null)
    {

        /*** create cache id ***/
        $cacheId = $id ? md5($id . basename($file)) : md5(basename($file));

        /*** create cache filename ***/
        $bstname = $this->cache_dir . '/' . $cacheId . '/' . basename($file);

        /*** create directory name for cache file ***/
        $directory = $this->cache_dir . '/' . $cacheId;

        /*** create cache directory ***/
        if (!is_dir($directory)) {
            mkdir($directory, 0775);
        }

        /*** write to cache ***/
        if (file_put_contents($bstname, $content) == FALSE) {
            throw new \Exception("Kan niet naar de cache schrijven");
        }
    }

    /**
     * Returns content cached file
     *
     * @param string $file Filename template
     *
     * @param string $id Cache file identification number/string for retrieval
     *
     * @access private
     *
     * @return string cached content on success, or FALSE
     *
     *
     */
    private function getCache($file, $id = null)
    {
        $cacheId = $id ? md5($id . basename($file)) : md5(basename($file));
        $bstname = $this->cache_dir . '/' . $cacheId . '/' . basename($file);

        /*** read cache file into variabele ***/
        $content = file_get_contents($bstname);
        return isset($content) ? $content : false;
    }

    /**
     * Removes cached files
     *
     * @access public
     *
     * @return void
     *   *
     */
    public function clearCache()
    {
        $cacheDir = realpath($this->cache_dir);
        $this->delDir($cacheDir);
    }

    /**
     * Removes files en folders recursive.
     *
     * @param string $dir Directory where files en folders live
     *
     * @access private
     *
     * @return void
     *
     *
     */
    private function delDir($dir)
    {

        /*** misschien een recursiveDirectoryIteratory hier ***/

        $deleteDir = realpath($dir);

        if ($handle = opendir($deleteDir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($deleteDir . '/' . $file)) {
                        $this->delDir($deleteDir . '/' . $file);
                        if (is_writable($deleteDir . '/' . $file)) {
                            rmdir($deleteDir . '/' . $file);
                        } else {
                            throw new \Exception("Kan Directory Niet Verwijderen");
                        }
                    } elseif (is_file($deleteDir . '/' . $file)) {
                        if (is_writable($deleteDir . '/' . $file)) {
                            unlink($deleteDir . '/' . $file);
                        } else {
                            throw new \Exception("Kan bestand niet verwijderen $deleteDir" . '/' . "$file");
                        }
                    }
                }
            }
            closedir($handle);
        }
    }
}
