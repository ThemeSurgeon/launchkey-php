<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;


use LaunchKey\SDK\Domain\PingResponse;
use LaunchKey\SDK\Event\PingResponseEvent;
use LaunchKey\SDK\EventDispatcher\EventDispatcher;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class BasicPingService implements PingService, LoggerAwareInterface
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var ApiService
     */
    private $apiService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ApiService $apiService, EventDispatcher $eventDispatcher)
    {
        $this->apiService = $apiService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Execute a ping request.
     *
     * @return PingResponse
     */
    public function ping()
    {
        if ($this->logger) $this->logger->debug("Initiating ping request");
        $response = $this->apiService->ping();
        if ($this->logger) $this->logger->debug("Ping response received", array("response" => $response));
        $this->eventDispatcher->dispatchEvent(PingResponseEvent::NAME, new PingResponseEvent($response));
        return $response;
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
