<?php namespace Framework\Config;

/**
* Class and Function List:
* Function list:
* - getInstance()
* - __construct()
* - getValue()
* - __clone()
* Classes list:
* - config
*/

/**
 * Singleton
 */
class Config
{
    
    /*
     * @var string $config_file
    */
    private static $config_file = '/config.php';
    
    /*
     * @var array $config_values;
    */
    public $config_values = array();
    
    /*
     * @var object $instance
    */
    private static $instance = null;
    
    /**
     * @access public
     *
     * @return object
     *
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Config;
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        $this->config_values = require (APP_PATH . self::$config_file);
    }
    
    /**
     * @get config optie with key
     *
     * @access public
     *
     * @param string $key: Key to retrieve config setting
     *
     * @return string
     *
     */
    public function getValue($key)
    {
        return self::$config_values[$key];
    }
    
    /**
     *
     * @__clone
     *
     * @access private
     *
     */
    private function __clone()
    {
    }
}
