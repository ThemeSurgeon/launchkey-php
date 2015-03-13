<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;

use LaunchKey\SDK\Domain\WhiteLabelUser;
use LaunchKey\SDK\Event\WhiteLabelUserCreatedEvent;
use LaunchKey\SDK\EventDispatcher\EventDispatcher;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for interacting with the LaunchKey Engine regarding White Label Groups
 * @package LaunchKey\SDK\Service
 */
class BasicWhiteLabelService implements WhiteLabelService
{
    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * @var ApiService
     */
    private $apiService;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var PingService
     */
    private $pingService;

    /**
     * @var LoggerAwareInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $appKey;

    /**
     * @param string $appKey App key from dashboard
     * @param string $secretKey Secret key for application from dashboard
     * @param ApiService $apiService
     * @param PingService $pingService
     * @param EventDispatcher $eventDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(
        $appKey,
        $secretKey,
        ApiService $apiService,
        PingService $pingService,
        EventDispatcher $eventDispatcher,
        LoggerInterface $logger = null
    )
    {
        $this->appKey = $appKey;
        $this->secretKey = $secretKey;
        $this->apiService = $apiService;
        $this->pingService = $pingService;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * @param string $identifier
     * @return WhiteLabelUser
     */
    public function createUser($identifier) {
        if ($this->logger) $this->logger->debug(
            "Initiating white label user create request",
            array("identifier" => $identifier)
        );
        $pingResponse = $this->pingService->ping();
        $user = $this->apiService->createWhiteLabelUser($identifier, $this->appKey, $this->secretKey, $pingResponse->getPublicKey());
        if ($this->logger) $this->logger->debug("White label user creates", array("user" => $user));
        $this->eventDispatcher->dispatchEvent(WhiteLabelUserCreatedEvent::NAME, new WhiteLabelUserCreatedEvent($user));
        return $user;
    }
}
