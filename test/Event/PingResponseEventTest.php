<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Event;


use LaunchKey\SDK\Domain\PingResponse;
use LaunchKey\SDK\Event\PingResponseEvent;

class PingResponseEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PingResponseEvent
     */
    private $event;

    /**
     * @Mock
     * @var PingResponse
     */
    private $pingResponse;

    public function testGetPingResponse()
    {
        $this->assertSame($this->pingResponse, $this->event->getPingResponse());
    }

    protected function setUp()
    {
        \Phake::initAnnotations($this);
        $this->event = new PingResponseEvent($this->pingResponse);
    }

    protected function tearDown()
    {
        $this->pingResponse = null;
        $this->event = null;
    }
}
