<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK;


use LaunchKey\SDK\Service\AuthService;
use LaunchKey\SDK\Service\BasicAuthService;
use LaunchKey\SDK\Service\EventDispatcher;
use LaunchKey\SDK\Service\PhpSecLibCryptService;
use LaunchKey\SDK\Service\BasicWhiteLabelService;
use LaunchKey\SDK\Service\CannedApiService;
use LaunchKey\SDK\Service\SynchronousLocalEventDispatcher;
use LaunchKey\SDK\Service\WhiteLabelService;

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
     * @var WhiteLabelService
     */
    private $whiteLabel;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    private function __construct($privateKey, $appKey, $secretKey, $config = null)
    {
        $cryptService = $this->getCryptService($privateKey);
        $apiService = $this->getApiService($appKey, $secretKey, $config);
        $this->eventDispatcher = new SynchronousLocalEventDispatcher();
        $this->auth = new BasicAuthService($cryptService, $apiService, $this->eventDispatcher);
        $this->whiteLabel = new BasicWhiteLabelService($cryptService, $apiService, $this->eventDispatcher);
    }

    /**
     * @return self
     */
    public static function factory($privateKey, $appKey, $secretKey)
    {
        $hash = md5($privateKey . $appKey . $secretKey);
        if (!isset(static::$instances[$hash])) {
            static::$instances[$hash] = new self($privateKey, $appKey, $secretKey);
        }
        return static::$instances[$hash];
    }

    public function auth() {
        $this->auth;
    }

    public function whiteLabel() {
        $this->whiteLabel;
    }

    /**
     * @return EventDispatcher
     */
    public function eventDispatcher()
    {
        return $this->eventDispatcher;
    }

    private function getApiService($appKey, $secretKey, $config)
    {
        return new CannedApiService();
    }

    private function getCryptService($privateKey)
    {
        return new PhpSecLibCryptService($privateKey);
    }
}
