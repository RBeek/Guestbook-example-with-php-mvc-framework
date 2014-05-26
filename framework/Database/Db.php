<?php namespace Framework\Database;

use Framework\Config\Config;

/**
 * Class and Function List:
 * Function list:
 * - __construct()
 * - getInstance()
 * - __clone()
 * Classes list:
 * - Db
 */

/**
 *
 * @Singleton for creating a database connection
 * *
 */
class Db
{

    /**
     * instance of self
     * @var $instance
     */
    private static $instance = NULL;

    private function __construct()
    {
    }

    /**
     *
     * @return object (PDO)
     *
     * @access public
     *
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            $config = Config::getInstance();

            $db_type = $config->config_values['connections']['mysql']['driver'];
            $db_host = $config->config_values['connections']['mysql']['host'];
            $db_name = $config->config_values['connections']['mysql']['database'];
            $db_user = $config->config_values['connections']['mysql']['username'];
            $db_pass = $config->config_values['connections']['mysql']['password'];
            $db_port = $config->config_values['connections']['mysql']['port'];

            self::$instance = new \PDO("$db_type:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass);
            self::$instance->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        return self::$instance;
    }

    private function __clone()
    {
    }
}
