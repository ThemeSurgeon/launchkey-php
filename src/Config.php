<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK;

use Psr\Log\LoggerInterface;

/**
 * Class Config
 * @package LaunchKey\SDK
 */
class Config
{
    /**
     * Seconds to cache ping requests.
     *
     * @var int
     */
    private $pingTTL = 60;

    /**
     * Secret key for the organization or application.
     *
     * @var string
     */
    private $secretKey;

    /**
     * Private key of the RSA private/public key pair for the organization or application.
     *
     * @var string
     */
    private $privateKey;

    /**
     * App key for an application
     * @var string
     */
    private $appKey;

    /**
     * @var string|Cache\Cache
     */
    private $cache;

    /**
     * @var string|EventDispatcher\EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Get the number of seconds a ping response will be cached.
     *
     * @return int
     */
    public function getPingTTL()
    {
        return $this->pingTTL;
    }

    /**
     * Set the number of seconds a ping response will be cached.
     *
     * @param mixed $pingTTL
     * @return $this
     */
    public function setPingTTL($pingTTL)
    {
        $this->pingTTL = $pingTTL;
        return $this;
    }

    /**
     * Get the secret key for the organization or application.
     *
     * @return string
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * Set the secret key for the organization or application.
     *
     * @param string $secretKey
     * @return $this
     */
    public function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;
        return $this;
    }

    /**
     * Get the private key of the RSA private/public key pair for the organization or application.
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Set the private key of the RSA private/public key pair for the organization or application.
     *
     * @param string $privateKey
     * @return $this
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
        return $this;
    }

    /**
     * Set the location of the file that contains the private key of the RSA private/public key pair for the
     * organization or application.
     *
     * @param string $location File location.  This may be a location of the local file system or a remote location
     * with a valid parseable URL by your PHP installation.
     * @return $this
     */
    public function setPrivateKeyLocation($location)
    {
        $resolvedLocation = preg_match("/.+:\/\/.+/", $location) ? $location : stream_resolve_include_path($location);
        if (!$resolvedLocation) {
            throw new \InvalidArgumentException("Unable to resolve location: " . $location);
        }

        $old = error_reporting(E_ERROR);
        $data = file_get_contents($location, FILE_USE_INCLUDE_PATH);
        error_reporting($old);
        if ($data === false) {
            throw new \InvalidArgumentException("Unable to obtain private key from location: " . $location);
        }
        $this->privateKey = $data;
        return $this;
    }

    /**
     * Get the app key for an application.
     *
     * @return string
     */
    public function getAppKey()
    {
        return $this->appKey;
    }

    /**
     * Set the app key for an application.
     *
     * @param string $appKey
     * @return $this
     */
    public function setAppKey($appKey)
    {
        $this->appKey = $appKey;
        return $this;
    }

    /**
     * @return Cache\Cache|string
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param Cache\Cache|string $cache
     * @return $this
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return EventDispatcher\EventDispatcher|string
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @param EventDispatcher\EventDispatcher|string $eventDispatcher
     * @return $this
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
