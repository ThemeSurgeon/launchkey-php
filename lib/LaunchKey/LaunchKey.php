<?php

namespace LaunchKey;

class LaunchKey {

    // Current library release version
    const VERSION = '0.1.0';

    // Current API version
    const API_VERSION = 'v1';

    public static $config;

    /**
     * @internal
     */
    public static $_client;

    public static function configure($options = array())
    {
        if ( ! self::$config)
        {
            self::$config = new Config;
        }

        self::$config->exchangeArray($options);
        self::$_client = new Client;

        return self::$config;
    }

    public static function __callStatic($method_name, $arguments)
    {
        call_user_func_array(self::$_client, $arguments);
    }

} // End LaunchKey
