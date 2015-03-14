<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use LaunchKey\SDK\Domain\PingResponse;
use LaunchKey\SDK\Domain\WhiteLabelUser;
use LaunchKey\SDK\EventDispatcher\EventDispatcher;
use LaunchKey\SDK\Service\ApiService;
use LaunchKey\SDK\Service\BasicWhiteLabelService;
use LaunchKey\SDK\Service\PingService;
use Psr\Log\LoggerInterface;

class BasicWhiteLabelServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @Mock
     * @var ApiService
     */
    private $apiService;

    /**
     * @Mock
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @Mock
     * @var WhiteLabelUser
     */
    private $whiteLabelUser;

    /**
     * @Mock
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var BasicWhiteLabelService
     */
    private $whitelabelService;


    public function testCreateUserCallsApiServiceWithIdentifier()
    {
        $this->whitelabelService->createUser("identifier");
        \Phake::verify($this->apiService)->createWhiteLabelUser("identifier");
    }

    public function testCreatUserReturnsUserFromApiService()
    {
        $actual = $this->whitelabelService->createUser("identifier");
        $this->assertSame($this->whiteLabelUser, $actual);
    }

    public function testCreateUserTriggersAppropriateWhiteLabelUserCreatedEvent()
    {
        $this->whitelabelService->createUser("identifier");
        $name = $event = null;
        \Phake::verify($this->eventDispatcher)->dispatchEvent(\Phake::capture($name), \Phake::capture($event));
        $this->assertEquals("launchkey.whitelabel.user.created", $name, "Unexpected event name");
        $this->assertInstanceOf('\LaunchKey\SDK\Event\WhiteLabelUserCreatedEvent', $event, "Unexpected event type");
        $this->assertSame($this->whiteLabelUser, $event->getWhiteLabelUser(), "Unexpected white label user in event");
    }

    public function testLoggerLogsDebugWhenAdded()
    {
        $this->whitelabelService = new BasicWhiteLabelService(
            $this->apiService,
            $this->eventDispatcher,
            $this->logger
        );
        $this->whitelabelService->createUser("identifier");
        \Phake::verify($this->logger, \Phake::atLeast(1))->debug(\Phake::anyParameters());
    }

    protected function setUp()
    {
        \Phake::initAnnotations($this);
        \Phake::when($this->apiService)
            ->createWhiteLabelUser(\Phake::anyParameters())
            ->thenReturn($this->whiteLabelUser);
        $this->whitelabelService = new BasicWhiteLabelService(
            $this->apiService,
            $this->eventDispatcher
        );
    }

    protected function tearDown()
    {
        $this->whitelabelService = null;
        $this->eventDispatcher = null;
        $this->whiteLabelUser = null;
        $this->logger = null;
    }
}
