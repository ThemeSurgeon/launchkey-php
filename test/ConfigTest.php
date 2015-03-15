<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test;


use LaunchKey\SDK\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Config
     */
    private $config;

    public function testSetGetPublicKeyTTL()
    {
        $this->assertEquals(9999, $this->config->setPublicKeyTTL(9999)->getPublicKeyTTL());
    }

    public function testPublicKeyTTLDefaultsTo60()
    {
        $this->assertEquals(60, $this->config->getPublicKeyTTL());
    }

    public function testSetAppSecretKey()
    {
        $this->assertEquals("app", $this->config->setAppKey("app")->getAppKey());
    }

    public function testSetGetSecretKey()
    {
        $this->assertEquals("secret", $this->config->setSecretKey("secret")->getSecretKey());
    }

    public function testSetGetPrivateKey()
    {
        $this->assertEquals("private", $this->config->setPrivateKey("private")->getPrivateKey());
    }

    public function testSetPrivateKeyLocationWorksWithFiles()
    {
        $location = __DIR__ . "/__fixtures/private_key.pem";
        $expected = file_get_contents($location);
        $actual = $this->config->setPrivateKeyLocation($location)->getPrivateKey();
        $this->assertEquals($expected, $actual);
    }

    public function testSetPrivateKeyLocationWorksWithURLs()
    {
        $location = 'file://' . __DIR__ . "/__fixtures/private_key.pem";
        $expected = file_get_contents($location);
        $actual = $this->config->setPrivateKeyLocation($location)->getPrivateKey();
        $this->assertEquals($expected, $actual);
    }

    public function testSetPrivateKeyLocationThrowsInvalidArgumentExceptionForInvalidLocation()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->config->setPrivateKeyLocation(__DIR__ . '/__invalid_location__');
    }

    public function testSetPrivateKeyLocationURLThrowsInvalidArgumentExceptionForInvalidURL()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->config->setPrivateKeyLocation('file://' . __DIR__ . '/__invalid_location__');
    }

    public function testGetEventDispatcherDefaultsToSynchronousLocalEventDispatcher()
    {
        $this->assertInstanceOf(
            '\LaunchKey\SDK\EventDispatcher\SynchronousLocalEventDispatcher',
            $this->config->getEventDispatcher()
        );
    }

    public function testSetGetEventDispatcher()
    {
        $config = new Config();
        $eventDispatcher = \Phake::mock('LaunchKey\SDK\EventDispatcher\EventDispatcher');
        $config->setEventDispatcher($eventDispatcher);
        $this->assertSame($eventDispatcher, $config->getEventDispatcher());
    }

    public function testGetCacheDefaultsToMemoryCache()
    {
        $config = new Config();
        $this->assertInstanceOf('\LaunchKey\SDK\Cache\MemoryCache', $config->getCache());
    }

    public function testSetGetCache()
    {
        $config = new Config();
        $cache = \Phake::mock('LaunchKey\SDK\Cache\Cache');
        $config->setCache($cache);
        $this->assertSame($cache, $config->getCache());
    }

    public function testSetGetLogger()
    {
        $log = \Phake::mock('Psr\Log\LoggerInterface');
        $this->assertSame($log, $this->config->setLogger($log)->getLogger());
    }

    public function testSetGetApiBaseUrl()
    {
        $this->assertEquals("endpoint", $this->config->setApiBaseUrl("endpoint")->getApiBaseUrl());
    }

    public function testGetApiBaseUrlDefaultsToProduction()
    {
        $this->assertEquals("https://api.launchkey.com", $this->config->getApiBaseUrl());
    }

    public function testSetGetPrivateKeyPassword()
    {
        $this->assertEquals("password", $this->config->setPrivateKeyPassword("password")->getPrivateKeyPassword());
    }

    public function testSetGetApiRequestTimeout()
    {
        $this->assertEquals(999, $this->config->setApiRequestTimeout(999)->getApiRequestTimeout());
    }

    public function testGetApiRequestTimeoutDefaultsTo0()
    {
        $this->assertEquals(0, $this->config->getApiRequestTimeout());
    }

    public function testSetGetApiConnectTimeout()
    {
        $this->assertEquals(999, $this->config->setApiConnectTimeout(999)->getApiConnectTimeout());
    }

    public function testGetApiConnectTimeoutDefaultsTo0()
    {
        $this->assertEquals(0, $this->config->getApiRequestTimeout());
    }

    protected function setUp()
    {
        $this->config = new Config();
    }

    protected function tearDown()
    {
        $this->config = null;
    }
}
