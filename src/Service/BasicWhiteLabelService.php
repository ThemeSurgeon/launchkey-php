<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;


use LaunchKey\SDK\Domain\WhiteLabelUser;

class BasicWhiteLabelService implements WhiteLabelService
{
    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * @var ApiService
     */
    private $httpService;

    /**
     * @param CryptService $cryptService
     * @param ApiService $httpService
     */
    public function __construct(CryptService $cryptService, ApiService $httpService)
    {
        $this->cryptService = $cryptService;
        $this->httpService = $httpService;
    }

    /**
     * @param string $identifier
     * @return WhiteLabelUser
     */
    public function createUser($identifier) {

    }
}
