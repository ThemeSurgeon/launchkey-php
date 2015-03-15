<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;


use LaunchKey\SDK\Cache\Cache;
use Psr\Log\LoggerInterface;

abstract class PublicKeyCachingAbstractApiService implements ApiService
{
    const CACHE_KEY_PUBLIC_KEY = "launchkey-public-key-cache";

    /**
     * @var int
     */
    private $publicKeyTTL;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var LoggerInterface
     */
    private $logger;

    function __construct(Cache $cache, $publicKeyTTL = 0, LoggerInterface $logger = null)
    {
        $this->publicKeyTTL = $publicKeyTTL;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Get the current RSA public key for the LaunchKey API
     *
     * @return string
     */
    protected function getPublicKey()
    {
        $response = null;
        $publicKey = null;
        try {
            $publicKey = $this->cache->get(static::CACHE_KEY_PUBLIC_KEY);
        } catch (\Exception $e) {
            if ($this->logger) $this->logger->error(
                "An error occurred on a cache get",
                array("key" => static::CACHE_KEY_PUBLIC_KEY, "Exception" => $e)
            );
        }

        if ($publicKey) {
            if ($this->logger) $this->logger->debug(
                "Public key cache hit",
                array("key" => static::CACHE_KEY_PUBLIC_KEY)
            );
        } else {
            if ($this->logger) $this->logger->debug(
                "Public key cache miss",
                array("key" => static::CACHE_KEY_PUBLIC_KEY)
            );
            $response = $this->ping();
            $publicKey = $response->getPublicKey();
            try {
                $this->cache->set(static::CACHE_KEY_PUBLIC_KEY, $publicKey, $this->publicKeyTTL);
                if ($this->logger) $this->logger->debug("Public key saved to cache");
            } catch (\Exception $e) {
                if ($this->logger) $this->logger->error(
                    "An error occurred on a cache set",
                    array("key" => static::CACHE_KEY_PUBLIC_KEY, "value" => $publicKey, "Exception" => $e)
                );
            }
        }
        return $publicKey;
    }
}
