<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK;

use Psr\Log\LoggerInterface;

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
     * @var Service\AuthService
     */
    private $auth;

    /**
     * @var Service\WhiteLabelService
     */
    private $whiteLabel;

    /**
     * @var EventDispatcher\EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var Cache\Cache
     */
    private $cache;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param Service\AuthService $authService
     * @param Service\WhiteLabelService $whiteLabelService
     * @param EventDispatcher\EventDispatcher $eventDispatcher
     * @param Cache\Cache $cache
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Service\AuthService $authService,
        Service\WhiteLabelService $whiteLabelService,
        EventDispatcher\EventDispatcher $eventDispatcher,
        Cache\Cache $cache,
        \Psr\Log\LoggerInterface $logger = null
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->auth = $authService;
        $this->whiteLabel = $whiteLabelService;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->cache = $cache;
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
     * @return self
     */
    public static function factory($appKey, $secretKey = null, $privateKey = null, Config $config = null)
    {
        $config = self::getUpdatedConfig($appKey, $secretKey, $privateKey, $config);
        $cache = $config->getCache();
        $logger = $config->getLogger();
        $eventDispatcher = $config->getEventDispatcher();

        $cryptService = new Service\PhpSecLibCryptService($config->getPrivateKey(), $config->getPrivateKeyPassword());
        $apiService = self::getGuzzleApiService(
            $config->getApiEndpoint(),
            $config->getApiConnectTimeout(),
            $config->getApiRequestTimeout(),
            $cryptService,
            $logger
        );

        $innerPingService = new Service\BasicPingService($apiService, $eventDispatcher, $logger);
        $pingService = new Service\CachingPingService($innerPingService, $cache, $config->getPingTTL(), $logger);

        $authService = new Service\BasicAuthService($apiService, $pingService, $eventDispatcher, $logger);
        $whiteLabelService = new Service\BasicWhiteLabelService($apiService, $pingService, $eventDispatcher, $logger);

        return new self($authService, $whiteLabelService, $eventDispatcher, $cache, $logger);
    }

    /**
     * @param $appKey
     * @param $secretKey
     * @param $privateKey
     * @param Config $config
     * @return Config
     */
    private static function getUpdatedConfig($appKey, $secretKey, $privateKey, $config)
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

        return $config;
    }

    /**
     * @param $endpoint
     * @param int $connectTimeout
     * @param int $requestTimeout
     * @param Service\CryptService $cryptService
     * @param LoggerInterface $logger
     * @return Service\GuzzleApiService
     */
    private static function getGuzzleApiService(
        $endpoint,
        $connectTimeout,
        $requestTimeout,
        Service\CryptService $cryptService,
        LoggerInterface $logger = null
    ) {
        $guzzle = new \Guzzle\Http\Client($endpoint, array(
            "redirect.disable" => true,
            'request.options' => array(
                "timeout" => $requestTimeout,
                "connect_timeout" => $connectTimeout,
            )
        ));

        if ($logger) {
            $guzzle->getEventDispatcher()->addListener(
                \Guzzle\Http\Client::CREATE_REQUEST,
                function (\Guzzle\Common\Event $event) use ($logger) {
                    $logger->debug("Guzzle preparing to send request", $event->toArray());
                }
            );
        }

        $apiService = new Service\GuzzleApiService(
            $guzzle,
            $cryptService,
            $logger
        );
        return $apiService;
    }

    /**
     * @return Service\AuthService
     */
    public function auth()
    {
        return $this->auth;
    }

    /**
     * @return Service\WhiteLabelService
     */
    public function whiteLabel()
    {
        return $this->whiteLabel;
    }

    /**
     * @return EventDispatcher\EventDispatcher
     */
    public function eventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @return Cache\Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
