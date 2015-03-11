<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Cache;


use LaunchKey\SDK\Cache\Cache;
use LaunchKey\SDK\Cache\MemoryCache;

class MemoryCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Cache
     */
    private $cache;

    public function testGetUnsetKeyReturnsNull()
    {
        $this->assertNull($this->cache->get("any key"));
    }

    public function testGetExpiredValueReturnsNull()
    {
        $this->cache->set("any key", "value", -1);
        $this->assertNull($this->cache->get("any key"));
    }

    public function testGetNonExpiredValueReturnsValue()
    {
        $this->cache->set("any key", "value", 999999999);
        $this->assertEquals("value", $this->cache->get("any key"));
    }

    public function testSetOverridesExistingValue()
    {
        $this->cache->set("any key", "value", 999999999);
        $this->cache->set("any key", "other value", 999999999);
        $this->assertEquals("other value", $this->cache->get("any key"));
    }

    protected function setUp()
    {
        $this->cache = new MemoryCache();
    }

    protected function tearDown()
    {
        $this->cache = null;
    }
}
