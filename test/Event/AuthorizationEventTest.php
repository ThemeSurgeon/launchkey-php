<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Event;


use LaunchKey\SDK\Event\AuthorizationEvent;

class AuthorizationEventTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructorSetsAuthId()
    {
        $event = new AuthorizationEvent("id", "status");
        $this->assertEquals("id", $event->getAuthRequestId());
    }

    public function testConstructorSetsStatus()
    {
        $event = new AuthorizationEvent("id", "status");
        $this->assertEquals("status", $event->getStatus());
    }

    public function testGetNameReturnsName()
    {
        $event = new AuthorizationEvent("id", "status");
        $this->assertEquals(AuthorizationEvent::NAME, $event->getName());
    }

    public function testSetNameThrowsLogicException()
    {
        $this->setExpectedException("\\LogicException");
        $event = new AuthorizationEvent("id", "status");
        $event->setName("Blah");
    }
}
