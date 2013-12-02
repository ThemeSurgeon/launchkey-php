<?php

namespace LaunchKey;

class LaunchKey {

    // Current library release version
    const VERSION = '0.1.0';

    // Current API version
    const API_VERSION = 'v1';

    public static $config;

    public static $client;

    public static function configure($options = array())
    {
        if ( ! self::$config)
        {
            self::$config = new Config;
        }

        self::$config->values($options);
        self::$client = new Client;

        return self::$config;
    }

    public static function __callStatic($method_name, $arguments)
    {
        if (empty($arguments))
        {
            return self::$client->$method_name();
        }
        else
        {
            return call_user_func_array(array(self::$client, $method_name), $arguments);
        }
    }

} // End LaunchKey
