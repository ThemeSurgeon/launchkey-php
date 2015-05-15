<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\EventDispatcher;


use LaunchKey\SDK\Event\AbstractEvent;
use LaunchKey\SDK\Event\Event;
use LaunchKey\SDK\EventDispatcher\SynchronousLocalEventDispatcher;
use Phake;

class SynchronousLocalEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractEvent
     */
    private $event;

    /**
     * @var CallableMock
     */
    private $callable1;

    /**
     * @var CallableMock
     */
    private $callable2;

    /**
     * @var SynchronousLocalEventDispatcher
     */
    private $eventDispatcher;

    public function testEventWithOtherNamesDoNotTrigger()
    {
        $this->eventDispatcher->subscribe("event-name", array($this->callable1, "callMe"));
        $this->eventDispatcher->dispatchEvent("other-event-name", $this->event);
        Phake::verifyNoInteraction($this->callable1);
    }

    public function testEventWithSameNameDoesTriggerCallableWithEvent()
    {
        $this->eventDispatcher->subscribe("event-name", array($this->callable1, "callMe"));
        $this->eventDispatcher->dispatchEvent("event-name", $this->event);
        Phake::verify($this->callable1)->callMe("event-name", $this->event);
    }

    public function testEventWithTwoSubscribersCallsBothSubscribers()
    {
        $this->eventDispatcher->subscribe("event-name", array($this->callable1, "callMe"));
        $this->eventDispatcher->subscribe("event-name", array($this->callable2, "callMe"));
        $this->eventDispatcher->dispatchEvent("event-name", $this->event);
        Phake::verify($this->callable1)->callMe("event-name", $this->event);
        Phake::verify($this->callable2)->callMe("event-name", $this->event);
    }

    public function testEventStopsPropagatingWhenListerCallsStopPropagation()
    {
        $this->eventDispatcher->subscribe("event-name", array($this->callable1, "callMe"), 10);
        $this->eventDispatcher->subscribe("event-name", array($this->callable2, "callMe"), 0);
        Phake::when($this->callable1)->callMe(Phake::anyParameters())->thenReturnCallback(function ($eventName, Event $event) {
            $event->stopPropagation();
        });
        $this->eventDispatcher->dispatchEvent("event-name", $this->event);
        Phake::verify($this->callable1)->callMe("event-name", $this->event);
        Phake::verifyNoInteraction($this->callable2);
    }

    public function testEventWithDifferentPriorityExecuteInmProperOrder()
    {
        $this->eventDispatcher->subscribe("event-name", array($this->callable1, "callMe"), -10);
        $this->eventDispatcher->subscribe("event-name", array($this->callable2, "callMe"), 10);
        $this->eventDispatcher->dispatchEvent("event-name", $this->event);

        Phake::inOrder(
            Phake::verify($this->callable2)->callMe("event-name", $this->event),
            Phake::verify($this->callable1)->callMe("event-name", $this->event)
        );
    }

    protected function setUp()
    {
        $this->event = Phake::partialMock('\LaunchKey\SDK\Event\AbstractEvent');
        $this->callable1 = Phake::mock('\LaunchKey\SDK\Test\EventDispatcher\CallableMock');
        $this->callable2 = Phake::mock('\LaunchKey\SDK\Test\EventDispatcher\CallableMock');
        $this->eventDispatcher = new SynchronousLocalEventDispatcher();
    }

    protected function tearDown()
    {
        $this->eventDispatcher = null;
        $this->event = null;
        $this->event2 = null;
    }

}

interface CallableMock {
    public function callMe($eventName, $event);
}
