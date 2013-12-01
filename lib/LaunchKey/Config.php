<?php

namespace LaunchKey;

/**
 * Config
 */
class Config extends \ArrayObject {

    private static $options = array(
        'domain'                    => NULL,
        'app_key'                   => NULL,
        'secret_key'                => NULL,
        'keypair'                   => NULL,
        'passphrase'                => NULL,
        'host'                      => 'api.launchkey.com',
        'use_system_ssl_cert_chain' => FALSE,
        'http_open_timeout'         => 2,
        'http_read_timeout'         => 5,
    );

    public function __construct($options = array(), $flags = 0, $iterator_class = 'ArrayIterator')
    {
        foreach ($options as $option => $value)
        {
            $this->assert_valid_option($option);
        }

        foreach (self::$options as $default => $value)
        {
            if ( ! array_key_exists($default, $options))
            {
                $options[$default] = $value;
            }
        }

        parent::__construct($options, $flags, $iterator_class);
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
        $this->assert_valid_option($option);
        return $this->offsetExists($option) ? parent::offsetGet($option) : NULL;
    }

    public function offsetSet($option, $value)
    {
        $this->assert_valid_option($option);
        return parent::offsetSet($option, $value);
    }

    private function assert_valid_option($option)
    {
        if ( ! array_key_exists($option, self::$options))
        {
            throw new Exception\LaunchKeyException(
                'specified option :option is not recognized', array(
                    ':option' => $option,
                )
            );
        }
    }

} // End Config
