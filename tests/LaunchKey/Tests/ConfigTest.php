<?php

namespace LaunchKey\Tests;

use LaunchKey\Config;

/**
 * Tests Config
 *
 * @package  LaunchKey
 * @category Tests
 */
class ConfigTest extends \PHPUnit_Framework_TestCase {

    private static $options = array(
        'domain', 'app_key', 'secret_key', 'passphrase', 'host',
        'use_system_ssl_cert_chain', 'http_open_timeout', 'http_read_timeout',
    );

    public function test_new_config_sets_defaults()
    {
        $config = new Config;

        $this->assertEquals('api.launchkey.com', $config['host']);
        $this->assertFalse($config['use_system_ssl_cert_chain']);
        $this->assertEquals(2, $config['http_open_timeout']);
        $this->assertEquals(5, $config['http_read_timeout']);
    }

    public function test_new_config_sets_supplied_options()
    {
        $options = array(
            'domain'                    => 'example.com',
            'app_key'                   => 'abcdefghijklmnopqrstuvwxyz',
            'secret_key'                => 's3cr3t!',
            'passphrase'                => 'supersecret',
            'host'                      => 'launchkey.example.com',
            'use_system_ssl_cert_chain' => TRUE,
            'http_open_timeout'         => 30,
            'http_read_timeout'         => 15,
        );

        $config = new Config($options);

        foreach ($options as $option => $expected)
        {
            $this->assertEquals($expected, $config[$option]);
        }
    }

    public function test_options_accessible_as_properties()
    {
        $config = new Config;

        foreach (self::$options as $option)
        {
            $config->$option = 'foo';
            $this->assertEquals('foo', $config->$option);
        }
    }

    public function test_options_accessible_as_array_keys()
    {
        $config = new Config;

        foreach (self::$options as $option)
        {
            $config[$option] = 'bar';
            $this->assertEquals('bar', $config[$option]);
        }
    }

    public function test_throws_exception_when_getting_invalid_options()
    {
        $error  = NULL;
        $config = new Config;

        try
        {
            $config['teehee'];
        }
        catch (\Exception $e)
        {
            $error = $e;
        }

        $this->assertNotNull($error);
        $this->assertTrue($error instanceof \LaunchKey\Exception\LaunchKeyException);
    }

     public function test_throws_exception_when_setting_invalid_options()
     {
        $error  = NULL;
        $config = new Config;

        try
        {
            $config['ಠ_ಠ'] = 'does not approve';
        }
        catch (\Exception $e)
        {
            $error = $e;
        }

        $this->assertNotNull($error);
        $this->assertTrue($error instanceof \LaunchKey\Exception\LaunchKeyException);
     }

    public function test_certificate_authority()
    {
        $config = new Config;

        $this->assertEquals(
            realpath(__DIR__.'/../../../resources/ca-bundle.crt'),
            $config['certificate_authority']
        );

        $config['use_system_ssl_cert_chain'] = TRUE;

        $this->assertEquals('system', $config['certificate_authority']);
    }

} // End ConfigTest
