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
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param CryptService $cryptService
     * @param ApiService $httpService
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(CryptService $cryptService, ApiService $httpService, EventDispatcher $eventDispatcher)
    {
        $this->cryptService = $cryptService;
        $this->httpService = $httpService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $identifier
     * @return WhiteLabelUser
     */
    public function createUser($identifier) {

    }

    /**
     * Set the event dispatcher
     * @param EventDispatcher $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }
}
