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

    public function testSetGetPingTTL()
    {
        $this->assertEquals(9999, $this->config->setPingTTL(9999)->getPingTTL());
    }

    public function testPingTTLDefaultsTo60()
    {
        $this->assertEquals(60, $this->config->getPingTTL());
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

    public function testSetGetCache()
    {
        $this->assertEquals("cache", $this->config->setCache("cache")->getCache());
    }

    public function testSetGetEventDispatcher()
    {
        $this->assertEquals("ed", $this->config->setEventDispatcher("ed")->getEventDispatcher());
    }

    public function testSetGetLogger()
    {
        $log = \Phake::mock('Psr\Log\LoggerInterface');
        $this->assertSame($log, $this->config->setLogger($log)->getLogger());
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
