<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Event;


use LaunchKey\SDK\Event\DeOrbitResponseEvent;

class DeOrbitResponseEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDeOrbitResponse()
    {
        $response = \Phake::mock('\LaunchKey\SDK\Domain\DeOrbitResponse');
        $event = new DeOrbitResponseEvent($response);
        $this->assertSame($response, $event->getDeOrbitResponse());
    }
}
