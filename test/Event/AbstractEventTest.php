<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Event;


use LaunchKey\SDK\Event\AbstractEvent;

class AbstractEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var AbstractEvent
     */
    private $event;

    public function testIsPropagationStoppedDefaultsToFalse()
    {
        $this->assertFalse($this->event->isPropagationStopped());
    }

    public function testIsPropagationStoppedIsTrueWhenStopPropagationIsCalled()
    {
        $this->event->stopPropagation();
        $this->assertTrue($this->event->isPropagationStopped());
    }

    protected function setUp()
    {
        $this->event = \Phake::partialMock('\LaunchKey\SDK\Event\AbstractEvent');
    }

    protected function tearDown()
    {
        $this->event = null;
    }
}
