<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use LaunchKey\SDK\Cache\Cache;
use LaunchKey\SDK\Domain\PingResponse;
use LaunchKey\SDK\Service\PublicKeyCachingAbstractApiService;
use Phake;
use Psr\Log\LoggerInterface;

class PublicKeyCachingAbstractApiServiceTest extends \PHPUnit_Framework_TestCase
{
    private $pingPublicKey = "Expected Ping PublicKey";
    /**
     * @var int
     */
    private $ttl = 9999;
    /**
     * @Mock
     * @var Cache
     */
    private $cache;

    /**
     * @var GetPublicKeyVisible
     */
    private $api;

    /**
     * @var GetPublicKeyVisible
     */
    private $loggingApi;

    /**
     * @Mock
     * @var LoggerInterface
     */
    private $logger;

    public function testGetPublicKeyReturnsCachedVersionWhenInCache()
    {
        Phake::when($this->cache)
            ->get(PublicKeyCachingAbstractApiService::CACHE_KEY_PUBLIC_KEY)
            ->thenReturn("Expected");
        $this->assertEquals("Expected", $this->api->getKey());
    }

    public function testGetPublicKeyDoesNotCallPingWhenInCache()
    {
        Phake::when($this->cache)
            ->get(PublicKeyCachingAbstractApiService::CACHE_KEY_PUBLIC_KEY)
            ->thenReturn("Expected");
        $this->api->getKey();
        Phake::verify($this->api, Phake::never())->ping(Phake::anyParameters());
    }

    public function testGetPublicKeyDoesSetCacheWhenInCache()
    {
        Phake::when($this->cache)
            ->get(PublicKeyCachingAbstractApiService::CACHE_KEY_PUBLIC_KEY)
            ->thenReturn("Expected");
        $this->api->getKey();
        Phake::verify($this->cache, Phake::never())->set(Phake::anyParameters());
    }

    public function testGetPublicKeyReturnsPingResponseKeyWhenNotInCache()
    {
        $this->assertEquals($this->pingPublicKey, $this->api->getKey());
    }

    public function testGetPublicKeyCachesPingResponseKeyWithProperTTLWhenNotInCache()
    {
        $this->api->getKey();
        Phake::verify($this->cache)->set(
            PublicKeyCachingAbstractApiService::CACHE_KEY_PUBLIC_KEY,
            $this->pingPublicKey,
            $this->ttl
        );
    }

    public function testServiceDebugLogsWhenLoggerIsPresent()
    {
        $this->loggingApi->getKey();
        Phake::verify($this->logger, Phake::atLeast(1))->debug(\Phake::anyParameters());
    }

    public function testServiceDoesNotErrLogWhenLoggerIsPresentButNoErrors()
    {
        $this->loggingApi->getKey();
        Phake::verify($this->logger, Phake::never())->errir(\Phake::anyParameters());
    }

    public function testServiceDoesNotErrorWhenCacheGetErrorsButReturnsPingPublicKey()
    {
        Phake::when($this->cache)->get(Phake::anyParameters())->thenThrow(new \Exception());
        $this->assertEquals($this->pingPublicKey, $this->api->getKey());
    }

    public function testServiceLogsErrorWhenCacheGetErrors()
    {
        Phake::when($this->cache)->get(Phake::anyParameters())->thenThrow(new \Exception());
        $this->loggingApi->getKey();
        Phake::verify($this->logger, Phake::atLeast(1))->error(Phake::anyParameters());
    }

    public function testServiceDoesNotErrorWhenCacheSetErrorsButReturnsPingPublicKey()
    {
        Phake::when($this->cache)->set(Phake::anyParameters())->thenThrow(new \Exception());
        $this->assertEquals($this->pingPublicKey, $this->api->getKey());
    }

    public function testServiceLogsErrorWhenCacheSetErrors()
    {
        Phake::when($this->cache)->set(Phake::anyParameters())->thenThrow(new \Exception());
        $this->loggingApi->getKey();
        Phake::verify($this->logger, Phake::atLeast(1))->error(Phake::anyParameters());
    }

    protected function setUp()
    {
        Phake::initAnnotations($this);
        $this->api = Phake::partialMock(
            'LaunchKey\SDK\Test\Service\GetPublicKeyVisible',
            $this->cache,
            $this->ttl
        );
        $this->loggingApi = Phake::partialMock(
            'LaunchKey\SDK\Test\Service\GetPublicKeyVisible',
            $this->cache,
            $this->ttl,
            $this->logger
        );
        $pingResponse = new PingResponse(new \DateTime(), $this->pingPublicKey, new \DateTime());
        Phake::when($this->api)->ping()->thenReturn($pingResponse);
        Phake::when($this->loggingApi)->ping()->thenReturn($pingResponse);
    }

    protected function tearDown()
    {
        $this->api = null;
        $this->loggingApi = null;
        $this->cache = null;
    }
}

abstract class GetPublicKeyVisible extends PublicKeyCachingAbstractApiService
{
    public function getKey()
    {
        return $this->getPublicKey();
    }
}
