<?php

namespace LaunchKey;

class LaunchKey {

    // Current library release version
    const VERSION = '0.1.1';

    // Current API version
    const API_VERSION = 'v1';

    /**
     * @var  LaunchKey\Config  Globally accessible configuration.
     * @see  configure()
     */
    public static $config;

    /**
     * @var  LaunchKey\Client  Globally accessible client.
     * @see  __callStatic()
     */
    public static $client;

    /**
     * Configures {@link $config}, with supplied array of `$options`.
     *
     * <code>
     * LaunchKey\LaunchKey::configure(array(
     *     'domain'     => 'yourdomain.tld',
     *     'app_key'    => 1234567890,
     *     'secret_key' => 'abcdefghijklmnopqrstuvwxyz',
     *     'keypair'    => file_get_contents('path/to/private_key.pem'),
     * ));
     * </code>
     *
     * @param   array  $options  Options to set on {@link LaunchKey\Config}.
     * @return  LaunchKey\Config
     */
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

    /**
     * Delegates missing static methods to {@link $client}.
     *
     * <code>
     * LaunchKey\LaunchKey::authorize('bob');
     *
     * // Same as:
     * $client = new LaunchKey\Client(LaunchKey\LaunchKey::$config);
     * $client->authorize('bob');
     * </code>
     *
     * @param   string  $method_name  Method to call on {@link LaunchKey\Client}.
     * @param   array   $arguments    Arguments to pass to called method.
     * @return  mixed   Return value of called method.
     */
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
