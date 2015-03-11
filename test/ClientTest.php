<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test;


use LaunchKey\SDK\Client;
use LaunchKey\SDK\Config;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Client
     */
    private $client;

    public function testFactoryWithSameParametersReturnsTheSameAuth()
    {
        $appKey = "APP_KEY";
        $secretKey = "SECRET_KEY";
        $privateKey = $this->getPrivateKey();
        $expected = Client::factory($appKey, $secretKey, $privateKey);
        $actual = Client::factory($appKey, $secretKey, $privateKey);
        $this->assertSame($expected, $actual);
    }

    public function testFactoryMethodWithDifferentAppKeysReturnsDifferentAuth()
    {
        $secretKey = "SECRET_KEY";
        $privateKey = $this->getPrivateKey();
        $expected = Client::factory("APP_KEY_ONE", $secretKey, $privateKey);
        $actual = Client::factory("APP_KEY_TWO", $secretKey, $privateKey);
        $this->assertNotSame($expected, $actual);
    }

    public function testFactoryMethodWithDifferentSecretKeysReturnsDifferentAuth()
    {
        $appKey = "APP_KEY";
        $privateKey = $this->getPrivateKey();
        $expected = Client::factory($appKey, "SECRET_KEY_ONE", $privateKey);
        $actual = Client::factory($appKey, "SECRET_KEY_TWO", $privateKey);
        $this->assertNotSame($expected, $actual);
    }

    public function testFactoryMethodWithDifferentPublicKeysReturnsDifferentAuth()
    {
        $appKey = "APP_KEY";
        $secretKey = "SECRET_KEY";
        $expected = Client::factory($appKey, $secretKey, $this->getPrivateKey());
        $actual = Client::factory($appKey, $secretKey, $this->getOtherPrivateKey());
        $this->assertNotSame($expected, $actual);
    }

    public function testAuthReturnsTheSameAuth()
    {
        $appKey = "APP_KEY";
        $secretKey = "SECRET_KEY";
        $privateKey = $this->getPrivateKey();
        $expected = Client::factory($appKey, $secretKey, $privateKey)->auth();
        $actual = Client::factory($appKey, $secretKey, $privateKey)->auth();
        $this->assertSame($expected, $actual);
    }

    public function testWhiteLabelReturnsTheSameWhiteLabel()
    {
        $appKey = "APP_KEY";
        $secretKey = "SECRET_KEY";
        $privateKey = $this->getPrivateKey();
        $expected = Client::factory($appKey, $secretKey, $privateKey)->whiteLabel();
        $actual = Client::factory($appKey, $secretKey, $privateKey)->whiteLabel();
        $this->assertSame($expected, $actual);
    }

    public function testEventDispatcherDefaultsToSynchronousLocalEventDispatcher()
    {
        $this->assertInstanceOf(
            '\LaunchKey\SDK\EventDispatcher\SynchronousLocalEventDispatcher',
            Client::factory("key", 'secret', $this->getPrivateKey())->eventDispatcher()
        );
    }

    public function testEventDispatcherUsesObjectInConfigWhenPresent()
    {
        $config = new Config();
        $eventDispatcher = \Phake::mock('LaunchKey\SDK\EventDispatcher\EventDispatcher');
        $config->setEventDispatcher($eventDispatcher);
        $client = Client::factory(uniqid("testEventDispatcherUsesObjectInConfigWhenPresent", true), $config);
        $this->assertSame($eventDispatcher, $client->eventDispatcher());
    }

    public function testEventDispatcherUsesClassNameWhenPresentInConfig()
    {
        $config = new Config();
        $config->setEventDispatcher('LaunchKey\SDK\EventDispatcher\SynchronousLocalEventDispatcher');
        $client = Client::factory(uniqid("testEventDispatcherUsesClassNameWhenPresentInConfig", true), $config);
        $this->assertInstanceOf('LaunchKey\SDK\EventDispatcher\SynchronousLocalEventDispatcher', $client->eventDispatcher());
    }

    public function testFactoryAppKeyInConfigIsTreatedTheSameAsAppKeyParameter()
    {
        $appKey = uniqid("testFactoryAppKeyInConfigIsTreatedTheSameAsAppKeyParameter");
        $config = new Config();
        $expected = Client::factory($appKey, "SECRET", $this->getPrivateKey());
        $config->setAppKey($appKey)
            ->setSecretKey("SECRET")
            ->setPrivateKey($this->getPrivateKey());
        $actual = Client::factory($config);
        $this->assertSame($expected, $actual);
    }

    public function testFactorySecretKeyInConfigIsTreatedTheSameAsSecretKeyParameter()
    {
        $appKey = uniqid("testFactorySecretKeyInConfigIsTreatedTheSameAsSecretKeyParameter");
        $secretKey = uniqid("testFactorySecretKeyInConfigIsTreatedTheSameAsSecretKeyParameter");
        $config = new Config();
        $config->setSecretKey($secretKey)
            ->setPrivateKey($this->getPrivateKey());
        $expected = Client::factory($appKey, $secretKey, $this->getPrivateKey());
        $actual = Client::factory($appKey, $config);
        $this->assertSame($expected, $actual);
    }

    public function testFactoryPrivateKeyKeyInConfigIsTreatedTheSameAsPublicKeyParameter()
    {
        $appKey = uniqid("testFactoryPrivateKeyKeyInConfigIsTreatedTheSameAsPublicKeyParameter");
        $secretKey = uniqid("testFactoryPrivateKeyKeyInConfigIsTreatedTheSameAsPublicKeyParameter");
        $config = new Config();
        $config->setPrivateKey($this->getPrivateKey());
        $expected = Client::factory($appKey, $secretKey, $this->getPrivateKey());
        $actual = Client::factory($appKey, $secretKey, $config);
        $this->assertSame($expected, $actual);
    }

    protected function setUp()
    {
        $this->client = Client::factory("APP_KEY", "SECRET_KEY", $this->getPrivateKey());
    }

    protected function tearDown()
    {
        $this->client = null;
    }

    protected function getPrivateKey()
    {
        static $key;
        if (!$key) {
            $key = file_get_contents(__DIR__ . "/__fixtures/private_key.pem");
        }
        return $key;
    }

    protected function getOtherPrivateKey()
    {
        static $key;
        if (!$key) {
            $key = file_get_contents(__DIR__ . "/__fixtures/other_private_key.pem");
        }
        return $key;
    }
}
