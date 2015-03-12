<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Domain;


use LaunchKey\SDK\Domain\DeOrbitCallback;

class DeOrbitCallbackTest extends \PHPUnit_Framework_TestCase
{
    public function testDeOrbitTimeDefaultsToTheCurrentTime()
    {
        $before = new \DateTime();
        $callback = new DeOrbitCallback();
        $after = new \DateTime();

        $this->assertGreaterThanOrEqual($before, $callback->getDeOrbitTime(), "Default time was earlier than expected");
        $this->assertLessThanOrEqual($after, $callback->getDeOrbitTime(), "Default time was earlier than expected");
    }

    public function testGetDeOrbitTime()
    {
        $time = new \DateTime();
        $callback = new DeOrbitCallback($time);
        $this->assertEquals($time, $callback->getDeOrbitTime());
    }

    public function testUserHashDefaultsToNull()
    {
        $callback = new DeOrbitCallback();
        $this->assertNull($callback->getUserHash());
    }

    public function testGetUserHashDefaults()
    {
        $callback = new DeOrbitCallback(null, "user-hash");
        $this->assertEquals("user-hash", $callback->getUserHash());
    }
}
