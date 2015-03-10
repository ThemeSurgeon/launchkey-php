<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Domain;


use LaunchKey\SDK\Domain\DeOrbitResponse;

class DeOrbitResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testDeOrbitTimeDefaultsToTheCurrentTime()
    {
        $before = new \DateTime();
        $response = new DeOrbitResponse();
        $after = new \DateTime();

        $this->assertGreaterThanOrEqual($before, $response->getDeOrbitTime(), "Default time was earlier than expected");
        $this->assertLessThanOrEqual($after, $response->getDeOrbitTime(), "Default time was earlier than expected");
    }

    public function testGetDeOrbitTime()
    {
        $time = new \DateTime();
        $response = new DeOrbitResponse($time);
        $this->assertEquals($time, $response->getDeOrbitTime());
    }

    public function testUserHashDefaultsToNull()
    {
        $response = new DeOrbitResponse();
        $this->assertNull($response->getUserHash());
    }

    public function testGetUserHashDefaults()
    {
        $response = new DeOrbitResponse(null, "user-hash");
        $this->assertEquals("user-hash", $response->getUserHash());
    }
}
