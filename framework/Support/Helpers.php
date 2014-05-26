<?php
namespace Framework\Support;

/**
 * Class and Function List:
 * Function list:
 * - ip_address()
 * - cookie()
 * - server()
 * - item()
 * - valid_ip()
 * - user_agent()
 * - with()
 * - str_contains()
 * Classes list:
 * - Helpers
 */

class Helpers
{
    private static $_ip_address = FALSE;
    private static $user_agent  = FALSE;

    public static function ip_address()
    {
        $prox = '';

        if (self::$_ip_address !== FALSE) {
            return self::$_ip_address;
        }

        if ($prox != '' && self::server('HTTP_X_FORWARDED_FOR') && self::server('REMOTE_ADDR')) {
            $proxies = preg_split('/[\s,]/', $prox, -1, PREG_SPLIT_NO_EMPTY);
            $proxies = is_array($proxies) ? $proxies : array($proxies);

            self::$_ip_address = in_array($_SERVER['REMOTE_ADDR'], $proxies) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        } elseif (self::server('REMOTE_ADDR') AND self::server('HTTP_CLIENT_IP')) {
            self::$_ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (self::server('REMOTE_ADDR')) {
            self::$_ip_address = $_SERVER['REMOTE_ADDR'];
        } elseif (self::server('HTTP_CLIENT_IP')) {
            self::$_ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (self::server('HTTP_X_FORWARDED_FOR')) {
            self::$_ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (self::$_ip_address === FALSE) {
            self::$_ip_address = '0.0.0.0';
            return self::$_ip_address;
        }

        if (strstr(self::$_ip_address, ',')) {
            $x = explode(',', self::$_ip_address);
            self::$_ip_address = trim(end($x));
        }

        if (!self::valid_ip(self::$_ip_address)) {
            self::$_ip_address = '0.0.0.0';
        }

        return self::$_ip_address;
    }

    /**
     * set of convenience functions for global arrays
     */
    public static function cookie($index = '')
    {
        return self::item($_COOKIE, $index);
    }

    public static function server($index = '')
    {
        return self::item($_SERVER, $index);
    }

    private static function item(&$array, $index = '')
    {
        return !isset($array[$index]) ? false : $array[$index];
    }

    /**
     * Determine if a given string is a valid ip address.
     *
     * @param  string  $ip
     * @return bool
     */
    public static function valid_ip($ip)
    {
        $ip_segments = explode('.', $ip);

        // Always 4 segments
        if (count($ip_segments) != 4) {
            return FALSE;
        }

        // IP cannot begin with 0
        if ($ip_segments[0][0] == '0') {
            return FALSE;
        }

        // Check each segment
        foreach ($ip_segments as $segment) {

            // IP segments must be numbers and the length cannot be longer then
            // 3 or greater then 255
            if ($segment == '' OR preg_match("/[^0-9]/", $segment) OR $segment > 255 OR strlen($segment) > 3) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * User Agent
     *
     * @access   public
     * @return   string
     */
    public static function user_agent()
    {
        if (self::$user_agent !== FALSE) return self::$user_agent;

        return self::$user_agent = (!isset($_SERVER['HTTP_USER_AGENT'])) ? FALSE : $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * Return the given object. Useful for chaining.
     *
     * @param  mixed  $object
     * @return mixed
     */
    public static function with($object)
    {
        return $object;
    }

    /**
     * Determine if a given string contains a given sub-string.
     *
     * @param  string        $haystack
     * @param  string|array  $needle
     * @return bool
     */
    public static function str_contains($haystack, $needle)
    {
        foreach ((array)$needle as $n) {
            if (strpos($haystack, $n) !== false) return true;
        }

        return false;
    }
}
