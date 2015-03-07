<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK;


use LaunchKey\SDK\Service\BasicAuthService;
use LaunchKey\SDK\Service\PhpSecLibCryptService;
use LaunchKey\SDK\Service\BasicWhiteLabelService;
use LaunchKey\SDK\Service\CannedApiService;

class Client
{
    /**
     * @var self
     */
    static $instances = array();

    private function __construct($privateKey, $appKey, $secretKey, $config = null)
    {
        $cryptService = $this->getCryptService($privateKey);
        $apiService = $this->getApiService($appKey, $secretKey, $config);
        $this->auth = new BasicAuthService($cryptService, $apiService);
        $this->whiteLabel = new BasicWhiteLabelService($cryptService, $apiService);
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

    private function getApiService($appKey, $secretKey, $config)
    {
        return new CannedApiService();
    }

    private function getCryptService($privateKey)
    {
        return new PhpSecLibCryptService($privateKey);
    }
}
