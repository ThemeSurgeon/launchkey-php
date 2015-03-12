<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Event;


use LaunchKey\SDK\Event\DeOrbitCallbackEvent;

class DeOrbitCallbackEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDeOrbitCallback()
    {
        $response = \Phake::mock('\LaunchKey\SDK\Domain\DeOrbitCallback');
        $event = new DeOrbitCallbackEvent($response);
        $this->assertSame($response, $event->getDeOrbitCallback());
    }
}
