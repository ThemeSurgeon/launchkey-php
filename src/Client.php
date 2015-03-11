<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK;

use LaunchKey\SDK\Service\BasicPingService;
use LaunchKey\SDK\Service\CachingPingService;

/**
 * LaunchKey SDK Client
 *
 * The client is a domain aggregate for the auth and white label services.  It provides a simple interface to creating
 * and using the underlying services with little knowledge of the services themselves.The client can only be created
 * from the factory method.
 *
 * @package LaunchKey\SDK
 */
class Client
{
    /**
     * @var self
     */
    private static $instances = array();

    /**
     * @var AuthService
     */
    private $auth;

    /**
     * @var Service\WhiteLabelService
     */
    private $whiteLabel;

    /**
     * @var Service\EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param Config $config
     */
    private function __construct(Config $config)
    {
        $this->eventDispatcher = $this->getEventDispatcher($config);
        $cryptService = $this->getCryptService($config);
        $apiService = $this->getApiService($config);
        $pingService = $this->getPingService($config, $apiService, $this->eventDispatcher);
        $this->auth = new Service\BasicAuthService($cryptService, $apiService, $pingService, $this->eventDispatcher);
        $this->whiteLabel =
            new Service\BasicWhiteLabelService($apiService, $pingService, $this->eventDispatcher);
    }

    /**
     * Build a LaunchKey SDK client utilizing the following parameters.  A \LaunchKey\SDk\Config object can be
     * substituted for any of the provided parameters.  The parameters before the config object will
     * override any values within the config object.  All parameters after the config object will be ignored.
     *
     * Example:
     *
     * $config = new \LaunchKey\SDK\Config();
     * $config->setAppKey("config-app-key");
     * $config->setPrivateKeyLocation("/usr/local/etc/private_key.pem");
     * $client = \LaunchKey\SDK\Client::factory("param-app-key", $config, "ignored-private-key");
     *
     * In the previous example, the config object is supplied for the privateKey parameter.  As such, the app key
     * value of "config-app-key" in the config object will be replaced with the "param-app-key" value passed to the
     * appKey parameter and the "ignored-private-key" value passed to the privateKey parameter will be ignored.
     *
     * @param string|Config $appKey
     * @param string|Config|null $secretKey
     * @param string|Config|null $privateKey
     * @param Config|null $config
     * @return Client
     */
    public static function factory($appKey, $secretKey = null, $privateKey = null, Config $config = null)
    {
        if ($appKey instanceof Config) {
            $config = $appKey;
        } elseif ($secretKey instanceof Config) {
            $config = $secretKey;
            $config->setAppKey($appKey);
        } elseif ($privateKey instanceof Config) {
            $config = $privateKey;
            $config->setSecretKey($secretKey);
            $config->setAppKey($appKey);
        } else {
            if (is_null($config)) {
                $config = new Config();
            }
            $config->setAppKey($appKey);
            $config->setSecretKey($secretKey);
            $config->setPrivateKey($privateKey);
        }
        $hash = md5(sprintf('%s|%s|%s', $config->getAppKey(), $config->getSecretKey(), $config->getPrivateKey()));
        if (!isset(static::$instances[$hash])) {
            static::$instances[$hash] = new self($config);
        }
        return static::$instances[$hash];
    }

    /**
     * @return Service\AuthService
     */
    public function auth()
    {
        $this->auth;
    }

    /**
     * @return Service\WhiteLabelService
     */
    public function whiteLabel()
    {
        $this->whiteLabel;
    }

    /**
     * @return Service\EventDispatcher
     */
    public function eventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @param $config
     * @return Service\ApiService
     */
    private function getApiService(Config $config)
    {
        return new Service\CannedApiService();
    }

    /**
     * @param Config $config
     * @return Service\CryptService
     */
    private function getCryptService(Config $config)
    {
        return new Service\PhpSecLibCryptService($config->getPrivateKey());
    }

    /**
     * @param Config $config
     * @param Service\ApiService $apiService
     * @return Service\PingService
     */
    private function getPingService(Config $config, Service\ApiService $apiService, EventDispatcher\EventDispatcher $eventDispatcher)
    {
        $configCache = $config->getCache();
        if ($configCache instanceof Cache\Cache) {
            $cache = $configCache;
        } elseif (!empty($configCache)) {
            $cache = new $configCache();
        } else {
            $cache = new Cache\MemoryCache();
        }

        $pingService = new BasicPingService($apiService, $eventDispatcher);
        $decorator = new CachingPingService($pingService, $cache, $config->getPingTTL());
        if ($logger = $config->getLogger()) {
            $pingService->setLogger($logger);
            $decorator->setLogger($logger);
        }
        return $decorator;
    }

    private function getEventDispatcher(Config $config)
    {
        $configDispatcher = $config->getEventDispatcher();
        if ($configDispatcher instanceof EventDispatcher\EventDispatcher) {
            $dispatcher = $configDispatcher;
        } elseif (!empty($configDispatcher)) {
            $dispatcher = new $configDispatcher();
        } else {
            $dispatcher = new EventDispatcher\SynchronousLocalEventDispatcher();
        }
        return $dispatcher;
    }
}
