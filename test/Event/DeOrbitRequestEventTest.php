<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Event;


use LaunchKey\SDK\Event\DeOrbitRequestEvent;

class DeOrbitRequestEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDeOrbitRequest()
    {
        $request = \Phake::mock('\LaunchKey\SDK\Domain\DeOrbitRequest');
        $event = new DeOrbitRequestEvent($request);
        $this->assertSame($request, $event->getDeOrbitRequest());
    }
}
