<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;


use LaunchKey\SDK\Domain\WhiteLabelUser;
use LaunchKey\SDK\EventDispatcher\EventDispatcher;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class BasicWhiteLabelService implements WhiteLabelService, LoggerAwareInterface
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
     * @param CryptService $cryptService
     * @param ApiService $apiService
     * @param PingService $pingService
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(
        ApiService $apiService,
        PingService $pingService,
        EventDispatcher $eventDispatcher
    )
    {
        $this->apiService = $apiService;
        $this->pingService = $pingService;
        $this->eventDispatcher = $eventDispatcher;
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

        //$user = $this->apiService->createWhiteLabelUser($identifier);
        if ($this->logger) $this->logger->debug("White label user creates", array("user" => $user));
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
