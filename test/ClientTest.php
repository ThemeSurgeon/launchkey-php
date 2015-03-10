<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test;


use LaunchKey\SDK\Client;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Client
     */
    private $client;

    protected function setUp()
    {
        $this->client = Client::factory("APP_KEY", "SECRET_KEY", $this->getPrivateKey());
    }

    protected function tearDown()
    {
        $this->client = null;
    }

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

    public function testEventDispatcherIsAnEventDispatcher()
    {
        $this->assertInstanceOf(
            '\LaunchKey\SDK\Service\EventDispatcher',
            Client::factory("key", 'secret', $this->getPrivateKey())->eventDispatcher()
        );
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
