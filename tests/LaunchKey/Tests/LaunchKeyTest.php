<?php

namespace LaunchKey\Tests;

use LaunchKey\LaunchKey;
use Mockery;

/**
 * Tests LaunchKey
 *
 * @package  LaunchKey
 * @category Tests
 */
class LaunchKeyTest extends \PHPUnit_Framework_TestCase
{

    public function test_configure_sets_supplied_options()
    {
        $options = array(
            'domain' => 'example.com',
            'app_key' => 'abcdefghijklmnopqrstuvwxyz',
            'secret_key' => 's3cr3t!',
            'passphrase' => 'supersecret',
            'host' => 'launchkey.example.com',
            'use_system_ssl_cert_chain' => TRUE,
            'http_open_timeout' => 30,
            'http_read_timeout' => 15,
        );

        LaunchKey::configure($options);

        foreach ($options as $option => $expected) {
            $this->assertEquals($expected, LaunchKey::$config[$option]);
        }
    }

    public function test_configure_instantiates_a_new_client()
    {
        LaunchKey::configure();
        $last_client = spl_object_hash(LaunchKey::$client);
        LaunchKey::configure();

        $this->assertNotEquals($last_client, spl_object_hash(LaunchKey::$client));
    }

    public function test_missing_static_methods_are_delegated_to_client()
    {
        LaunchKey::$client = Mockery::mock('LaunchKey\\LaunchKey');
        LaunchKey::$client->shouldReceive('authorize')
            ->with('bob_bobson')
            ->andReturn('foo');

        $this->assertEquals('foo', LaunchKey::authorize('bob_bobson'));
    }

    protected function setUp()
    {
        parent::setUp();

        $this->original_config = LaunchKey::$config;
        $this->original_client = LaunchKey::$client;
    }

    protected function tearDown()
    {
        parent::tearDown();

        LaunchKey::$config = $this->original_config;
        LaunchKey::$client = $this->original_client;
    }

} // End LaunchKeyTest
