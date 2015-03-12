<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use LaunchKey\SDK\Cache\Cache;
use LaunchKey\SDK\Cache\CacheError;
use LaunchKey\SDK\Domain\PingResponse;
use LaunchKey\SDK\Service\CachingPingService;
use LaunchKey\SDK\Service\Exception\CommunicationError;
use LaunchKey\SDK\Service\PingService;
use Psr\Log\LoggerInterface;

class CachingPingServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var int
     */
    private $ttl;

    /**
     * @Mock
     * @var Cache
     */
    private $cache;

    /**
     * @Mock
     * @var PingService
     */
    private $pingService;

    /**
     * @var PingResponse
     */
    private $pingResponse;

    /**
     * @var CachingPingService
     */
    private $cachingPingService;

    /**
     * @var CachingPingService
     */
    private $loggingCachingPingService;

    /**
     * @Mock
     * @var LoggerInterface
     */
    private $logger;

    public function testCacheHitReturnsExpectedPingResponse()
    {
        $expected = new PingResponse(new \DateTime('99 minutes'), "expected key", new \DateTime('-100 minutes'));
        \Phake::when($this->cache)->get(\Phake::anyParameters())->thenReturn($expected->toJson());
        $actual = $this->cachingPingService->ping();
        $this->assertEquals($expected, $actual);
    }

    public function testCacheHitDoesNotCacheResponse()
    {
        \Phake::when($this->cache)->get(\Phake::anyParameters())->thenReturn($this->pingResponse->toJson());
        $this->cachingPingService->ping();
        \Phake::verify($this->cache, \Phake::never())->set(\Phake::anyParameters());
    }

    public function testCacheMissCallsPingService()
    {
        $this->cachingPingService->ping();
        \Phake::verify($this->pingService)->ping();
    }

    public function testCacheMissReturnsPingResponseFromPingService()
    {
        $actual = $this->cachingPingService->ping();
        $this->assertSame($this->pingResponse, $actual);
    }

    public function testCacheMissCachesWithCorrectKeyAndTtlAndReturnedResponseFromPingService()
    {
        $this->cachingPingService->ping();
        $key = $value = $ttl = null;
        \Phake::verify($this->cache)->set(\Phake::capture($key), \Phake::capture($value), \Phake::capture($ttl));
        $this->assertEquals("launchkey-ping-service-cache", $key, "Unexpected value for cache key");
        $this->assertEquals($this->pingResponse->toJson(), $value, "Unexpected value for cache value");
        $this->assertEquals($this->ttl, $ttl, "Unexpected value for cache TTL");
    }

    public function testCacheErrorOnGetCallsPingService()
    {
        \Phake::when($this->cache)->get(\Phake::anyParameters())->thenThrow(new CommunicationError());
        $this->cachingPingService->ping();
        \Phake::verify($this->pingService)->ping();
    }

    public function testCacheErrorOnGetReturnsPingResponseFromPingService()
    {
        \Phake::when($this->cache)->get(\Phake::anyParameters())->thenThrow(new CommunicationError());
        $actual = $this->cachingPingService->ping();
        $this->assertEquals($this->pingResponse, $actual);
    }

    public function testCacheErrorOnGetCachesResponseFromPingService()
    {
        \Phake::when($this->cache)->get(\Phake::anyParameters())->thenThrow(new CommunicationError());
        $this->cachingPingService->ping();
        $key = $value = $ttl = null;
        \Phake::verify($this->cache)->set(\Phake::capture($key), \Phake::capture($value), \Phake::capture($ttl));
        $this->assertEquals("launchkey-ping-service-cache", $key, "Unexpected value for cache key");
        $this->assertEquals($this->pingResponse->toJson(), $value, "Unexpected value for cache value");
        $this->assertEquals($this->ttl, $ttl, "Unexpected value for cache TTL");
    }

    public function testCacheErrorOnGetLogsWhenLoggerIsPresent()
    {
        \Phake::when($this->cache)->get(\Phake::anyParameters())->thenThrow(new CacheError());
        $this->loggingCachingPingService->ping();
        \Phake::verify($this->logger)->error(\Phake::anyParameters());
    }

    public function testCacheErrorOnSetStillReturnsPingResponse()
    {
        \Phake::when($this->cache)->set(\Phake::anyParameters())->thenThrow(new CacheError());
        $actual = $this->cachingPingService->ping();
        $this->assertSame($this->pingResponse, $actual);
    }

    public function testCacheErrorOnSetLogsWhenLoggerIsPresent()
    {
        \Phake::when($this->cache)->set(\Phake::anyParameters())->thenThrow(new CacheError());
        $this->loggingCachingPingService->ping();
        \Phake::verify($this->logger)->error(\Phake::anyParameters());
    }

    public function testPingServiceErrorBubbles()
    {
        $expected = new CacheError("Expected message");
        $this->setExpectedException(get_class($expected), $expected->getMessage(), $expected->getCode());
        \Phake::when($this->pingService)->ping()->thenThrow($expected);
        $this->cachingPingService->ping();
    }

    protected function setUp()
    {
        \Phake::initAnnotations($this);
        $this->ttl = 999;
        $this->cachingPingService = new CachingPingService($this->pingService, $this->cache, $this->ttl);
        $this->loggingCachingPingService = new CachingPingService($this->pingService, $this->cache, $this->ttl, $this->logger);
        $this->pingResponse = new PingResponse(new \DateTime("-10 minutes"), "Key", new \DateTime("-20 minutes"));
        \Phake::when($this->pingService)->ping()->thenReturn($this->pingResponse);
    }

    protected function tearDown()
    {
        $this->ttl = null;
        $this->cache = null;
        $this->pingService = null;
        $this->cachingPingService = null;
    }
}
