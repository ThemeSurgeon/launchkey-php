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
     * @var PingService
     */
    private $pingService;

    /**
     * @Mock
     * @var PingResponse
     */
    private $pingResponse;

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
        $this->assertEquals("launchkey.white-label.user-created", $name, "Unexpected event name");
        $this->assertInstanceOf('\LaunchKey\SDK\Event\WhiteLabelUserCreated', $event, "Unexpected event type");
        $this->assertSame($this->whiteLabelUser, $event->getWhiteLabelUser(), "Unexpected white label user in event");
    }

    protected function setUp()
    {
        $this->markTestSkipped("Code not ready");
        \Phake::initAnnotations($this);
        \Phake::when($this->apiService)
            ->createWhiteLabelUser(\Phake::anyParameters())
            ->thenReturn($this->whiteLabelUser);
        \Phake::when($this->pingService)
            ->ping()
            ->thenReturn($this->pingResponse);

        $this->whitelabelService = new BasicWhiteLabelService(
            $this->pingService,
            $this->apiService,
            $this->evenDispatcher
        );
    }

    protected function tearDown()
    {
        $this->whitelabelService = null;
        $this->eventDispatcher = null;
        $this->whiteLabelUser = null;
        $this->logger = null;
        $this->pingResponse = null;
        $this->pingService = null;
    }
}
