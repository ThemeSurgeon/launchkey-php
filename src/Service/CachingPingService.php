<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;

use LaunchKey\SDK\Cache\Cache;
use LaunchKey\SDK\Domain\PingResponse;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Ping service decorator to cache ping requests for the provided TTL utilizing the provided cache implementation.
 *
 * @package LaunchKey\SDK\Service
 */
class CachingPingService implements PingService
{

    private static $key = "launchkey-ping-service-cache";

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PingService
     */
    private $pingService;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var int
     */
    private $ttl;

    function __construct(PingService $pingService, Cache $cache, $ttl, LoggerInterface $logger = null)
    {
        $this->pingService = $pingService;
        $this->cache = $cache;
        $this->ttl = $ttl;
        $this->logger = $logger;
    }

    /**
     * Execute a ping request.
     *
     * @return PingResponse
     */
    public function ping()
    {
        $response = null;
        try {
            $serialized = $this->cache->get(static::$key);
            if ($serialized) {
                $this->logDebug("Ping response cache hit", $serialized);
                $response = PingResponse::fromJson($serialized);
            }
        } catch (\Exception $e) {
            $this->logException($e, "An error occurred attempting to get cached ping responses from cache");
        }

        if (!$response) {
            $response = $this->pingService->ping();
            try {
                $this->logDebug("Response saved to cache", $response);
                $this->cache->set(static::$key, $response->toJson(), $this->ttl);
            } catch (\Exception $e) {
                $this->logException($e, "An error occurred attempting to cache a ping response");
            }
        }
        return $response;
    }

    /**
     * @param $exception
     */
    private function logException($exception, $message)
    {
        if ($this->logger) {
            $this->logger->error($message, array(
                'Exception' => $exception,
                'Cache Key' => static::$key
            ));
        }
    }

    /**
     * @param $response
     */
    private function logDebug($message, $data)
    {
        if ($this->logger) {
            $this->logger->debug(
                $message,
                array("key" => static::$key, "ttl" => $this->ttl, "value" => $data)
            );
        }
    }
}
