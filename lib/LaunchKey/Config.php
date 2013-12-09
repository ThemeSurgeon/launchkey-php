<?php

namespace LaunchKey;

/**
 * Handles configuration of {@link Client}.
 *
 * @property  LaunchKey\RSAKey|string  $api_public_key  Public key of LaunchKey's REST API.
 * @property  LaunchKey\RSAKey|string  $keypair  The application's RSA keypair.
 * @property  string  $app_key  The application key.
 * @property  string  $domain  The domain and host of the application.
 * @property  string  $host  The API host to use (defaults to `api.launchkey.com).
 * @property  integer  $http_open_timeout  The HTTP open timeout in seconds (defaults to `2`).
 * @property  integer  $http_read_timeout  The HTTP read timeout in seconds (defaults to `5`).
 * @property  string  $passphrase  The passphrase used to decrypt the {@link  $keypair}.
 * @property  boolean  $use_system_ssl_cert_chain  `TRUE` to use whatever CAs OpenSSL has installed on your system, `FALSE` to use the `ca-bundle.crt` file included in LaunchKey itself (recommended and default).
 *
 * @see Client::__construct()
 * @see LaunchKey::configure()
 */
class Config extends \ArrayObject {

    private static $options = array(
        'api_public_key',
        'app_key',
        'domain',
        'host',
        'http_open_timeout',
        'http_read_timeout',
        'keypair',
        'passphrase',
        'secret_key',
        'use_system_ssl_cert_chain',
    );

    private static $defaults = array(
        'app_key'                   => NULL,
        'domain'                    => NULL,
        'host'                      => 'api.launchkey.com',
        'http_open_timeout'         => 2,
        'http_read_timeout'         => 5,
        'passphrase'                => NULL,
        'secret_key'                => NULL,
        'use_system_ssl_cert_chain' => FALSE,
    );

    private $_api_public_key = NULL;

    private $_keypair = NULL;

    private $_raw_keypair;

    public function __construct($options = array(), $flags = 0, $iterator_class = 'ArrayIterator')
    {
        parent::__construct(array(), $flags, $iterator_class);
        $this->values($options);
    }

    public function values($options = array())
    {
        foreach ($options as $option => $value)
        {
            $this->assert_valid_option($option);
        }

        foreach (self::$defaults as $default => $value)
        {
            if ( ! array_key_exists($default, $options))
            {
                $options[$default] = $value;
            }
        }

        foreach ($options as $option => $value)
        {
            $this->offsetSet($option, $value);
        }
    }

    /**
     * @internal
     */
    public function certificate_authority()
    {
        if ($this['use_system_ssl_cert_chain'])
        {
            return 'system';
        }
        else
        {
            return realpath(__DIR__.'/../../resources/ca-bundle.crt');
        }
    }

    /**
     * @internal
     */
    public function api_public_key($value = NULL)
    {
        if ($value === NULL)
        {
            return $this->_api_public_key;
        }

        return $this->_api_public_key = new RSAKey($value);
    }

    /**
     * @internal
     */
    public function keypair($value = NULL)
    {
        if ($value === NULL)
        {
            if ($this->_keypair === NULL)
            {
                $this->_keypair = new RSAKey($this->_raw_keypair, array(
                    'passphrase' => $this['passphrase'],
                ));
            }

            return $this->_keypair;
        }

        $this->_keypair = NULL;
        return $this->_raw_keypair = $value;
    }

    public function __get($option)
    {
        return $this->offsetGet($option);
    }

    public function __set($option, $value)
    {
        return $this->offsetSet($option, $value);
    }

    public function __isset($option)
    {
        return $this->offsetExists($option);
    }

    public function __unset($option)
    {
        return $this->offsetUnset($option);
    }

    public function offsetGet($option)
    {
        if (method_exists($this, $option))
        {
            return $this->$option();
        }

        $this->assert_valid_option($option);
        return $this->offsetExists($option) ? parent::offsetGet($option) : NULL;
    }

    public function offsetSet($option, $value)
    {
        if (method_exists($this, $option))
        {
            return $this->$option($value);
        }

        $this->assert_valid_option($option);
        return parent::offsetSet($option, $value);
    }

    private function assert_valid_option($option)
    {
        if ( ! in_array($option, self::$options))
        {
            throw new Exception\LaunchKeyException(
                'specified option :option is not recognized', array(
                    ':option' => $option,
                )
            );
        }
    }

} // End Config
