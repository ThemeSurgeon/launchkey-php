<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test;

use LaunchKey\SDK\Client;
use LaunchKey\SDK\Config;
use LaunchKey\SDK\EventDispatcher\SynchronousLocalEventDispatcher;
use Phake;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $client;

    public function testFactoryReturnsClient()
    {
        $this->assertInstanceOf('LaunchKey\SDK\Client', $this->client);
    }

    public function testFactoryReturnsClientWithValidAuth()
    {
        $this->assertInstanceOf('LaunchKey\SDK\Service\AuthService', $this->client->auth());
    }

    public function testFactoryReturnsClientWithValidWhiteLabelService()
    {
        $this->assertInstanceOf('LaunchKey\SDK\Service\WhiteLabelService', $this->client->whiteLabel());
    }

    public function testFactoryConfigForAppKeyIgnoresAllOtherParameters()
    {
        $config = new Config();
        Client::factory($config, "SECRET", "private key");
        $this->assertNull($config->getAppKey(), "Unexpected App Key Value");
        $this->assertNull($config->getSecretKey(), "Unexpected Secret Key Value");
        $this->assertNull($config->getPrivateKey(), "Unexpected Private Key Value");
    }

    public function testFactoryConfigForSecretKeyOverridesAppKeyAndIgnoresAllOtherParameters()
    {
        $config = new Config();
        Client::factory("APP KEY", $config, "private key");
        $this->assertEquals("APP KEY", $config->getAppKey());
        $this->assertNull($config->getSecretKey(), "Unexpected Secret Key Value");
        $this->assertNull($config->getPrivateKey(), "Unexpected Private Key Value");
    }

    public function testFactoryConfigForPrivateKeyOverridesAppKeyAndSecretKeyAndIgnoresAllOtherParameters()
    {
        $config = new Config();
        Client::factory("APP KEY", "SECRET KEY", $config);
        $this->assertEquals("APP KEY", $config->getAppKey());
        $this->assertEquals("SECRET KEY", $config->getSecretKey());
        $this->assertNull($config->getPrivateKey(), "Unexpected Private Key Value");
    }

    public function testFactoryConfigForPrivateKeyOverridesAll()
    {
        $config = new Config();
        Client::factory("APP KEY", "SECRET KEY", "PRIVATE KEY", $config);
        $this->assertEquals("APP KEY", $config->getAppKey());
        $this->assertEquals("SECRET KEY", $config->getSecretKey());
        $this->assertEquals("PRIVATE KEY", $config->getPrivateKey());
    }

    public function testSettingConfigLoggerSetsTheLogger()
    {
        $config = new Config();
        $logger = \Phake::mock('Psr\Log\LoggerInterface');
        $config->setLogger($logger);
        $client = Client::factory($config);
        $this->assertEquals($logger, $client->getLogger());
    }

    public function testSettingConfigCacheSetsTheCache()
    {
        $config = new Config();
        $cache = \Phake::mock('\LaunchKey\SDK\Cache\Cache');
        $config->setCache($cache);
        $client = Client::factory($config);
        $this->assertEquals($cache, $client->getCache());
    }

    public function testFactorySetsTheEventDispatcher()
    {
        $expected = new SynchronousLocalEventDispatcher();
        $config = new Config();
        $config->setEventDispatcher($expected);
        $client = Client::factory($config);
        $this->assertSame($expected, $client->eventDispatcher());
    }

    public function testFactoryEventDispatcherForGuzzleClientLogsWhenEventDispatch()
    {
        $config = new Config();
        $config->setLogger(Phake::mock('\Psr\Log\LoggerInterface'));
        $config->setApiBaseUrl("Invalid URL to prevent making actual calls");
        $authService = Client::factory($config)->auth();
        try {
            $authService->deOrbit("Request ID");
        } catch (\Exception $e) {
            // An exception should be thrown since the URL is invalid
        }
        Phake::verify($config->getLogger())->debug("Guzzle preparing to send request", $this->anything());
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
