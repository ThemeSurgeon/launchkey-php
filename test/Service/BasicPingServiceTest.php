<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use LaunchKey\SDK\Domain\PingResponse;
use LaunchKey\SDK\Event\PingResponseEvent;
use LaunchKey\SDK\EventDispatcher\EventDispatcher;
use LaunchKey\SDK\Service\ApiService;
use LaunchKey\SDK\Service\BasicPingService;
use LaunchKey\SDK\Service\Exception\CommunicationError;

class BasicPingServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @Mock
     * @var ApiService
     */
    private $apiService;

    /**
     * @Mock
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @Mock
     * @var PingResponse
     */
    private $pingResponse;

    /**
     * @var BasicPingService
     */
    private $pingService;

    public function testPingCallPingOnApiService()
    {
        $this->pingService->ping();
        \Phake::verify($this->apiService)->ping();
    }

    public function testPingReturnsResponseFromApiService()
    {
        $expected = new PingResponse(new \DateTime("-10 minutes"), "public key", new \DateTime("+10 minutes"));
        \Phake::when($this->apiService)->ping()->thenReturn($expected);
        $actual = $this->pingService->ping();
        $this->assertSame($expected, $actual);
    }

    public function testPingTriggersPingResponseNamedEvent()
    {
        $this->pingService->ping();
        $event = $name = null;
        \Phake::verify($this->eventDispatcher)->dispatchEvent(\Phake::capture($name), \Phake::capture($event));
        $this->assertEquals(PingResponseEvent::NAME, $name, "Unexpected event name");
        $this->assertInstanceOf('LaunchKey\SDK\Event\PingResponseEvent', $event, "Unexpected event type");
        $this->assertEquals($this->pingResponse, $event->getPingResponse(), "Unexpected PingResponse object");
        return $event;
    }

    public function testPingErrorDoesNotTriggerPingResponseEvent()
    {
        \Phake::when($this->apiService)->ping()->thenThrow(new CommunicationError());
        try {
            $this->pingService->ping();
            $this->fail('Expected LaunchKey\SDK\Service\Exception\CommunicationError to be thrown');
        } catch(CommunicationError $error) {
            // Intentionally left blank
        }
        \Phake::verifyNoInteraction($this->eventDispatcher);
    }

    public function testApiServiceErrorBubblesOut()
    {
        $this->setExpectedException('LaunchKey\SDK\Service\Exception\CommunicationError');
        \Phake::when($this->apiService)->ping()->thenThrow(new CommunicationError());
        $this->pingService->ping();
    }

    protected function setUp()
    {
        \Phake::initAnnotations($this);
        \Phake::when($this->apiService)->ping()->thenReturn($this->pingResponse);
        $this->pingService = new BasicPingService($this->apiService, $this->eventDispatcher);
    }

    protected function tearDown()
    {
        $this->pingService = null;
        $this->apiService = null;
        $this->eventDispatcher = null;
    }
}
