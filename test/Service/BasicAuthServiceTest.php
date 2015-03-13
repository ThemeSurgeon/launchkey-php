<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use LaunchKey\SDK\Domain\AuthRequest;
use LaunchKey\SDK\Domain\AuthResponse;
use LaunchKey\SDK\Domain\DeOrbitCallback;
use LaunchKey\SDK\Domain\PingResponse;
use LaunchKey\SDK\Event\AuthRequestEvent;
use LaunchKey\SDK\Event\AuthResponseEvent;
use LaunchKey\SDK\Event\DeOrbitCallbackEvent;
use LaunchKey\SDK\Event\DeOrbitRequestEvent;
use LaunchKey\SDK\EventDispatcher\EventDispatcher;
use LaunchKey\SDK\Service\ApiService;
use LaunchKey\SDK\Service\BasicAuthService;
use LaunchKey\SDK\Service\Exception\CommunicationError;
use LaunchKey\SDK\Service\Exception\UnknownCallbackActionError;
use LaunchKey\SDK\Service\PingService;
use Phake;
use Psr\Log\LoggerInterface;

class BasicAuthServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BasicAuthService
     */
    private $authService;

    /**
     * @var BasicAuthService
     */
    private $loggingAuthService;

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
     * @var PingResponse
     */
    private $pingResponse;

    /**
     * @Mock
     * @var AuthResponse
     */
    private $authResponse;

    /**
     * @Mock
     * @var AuthRequest
     */
    private $authRequest;

    /**
     * @Mock
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @Mock
     * @var LoggerInterface
     */
    private $logger;

    public function testAuthorizeCallsPingService()
    {
        $this->authService->authorize(null);
        Phake::verify($this->pingService)->ping();
    }

    public function testAuthorizePassesPublicKeyFromPingResponseToApiService()
    {
        $this->authService->authorize(null);
        Phake::verify($this->apiService)->auth(
            $this->anything(),
            $this->anything(),
            $this->anything(),
            $this->anything(),
            $this->pingResponse->getPublicKey()
        );
    }

    public function testAuthorizePassesAppKeyToApiService()
    {
        $this->authService->authorize(null);
        Phake::verify($this->apiService)->auth(
            $this->anything(),
            $this->anything(),
            "APP KEY",
            $this->anything(),
            $this->anything()
        );
    }

    public function testAuthorizePassesSecretKeyToApiService()
    {
        $this->authService->authorize(null);
        Phake::verify($this->apiService)->auth(
            $this->anything(),
            $this->anything(),
            $this->anything(),
            "SECRET KEY",
            $this->anything()
        );
    }

    public function testAuthorizePassesUsernameToAuthService()
    {
        $this->authService->authorize("username");
        Phake::verify($this->apiService)->auth(
            "username",
            $this->anything(),
            $this->anything(),
            $this->anything(),
            $this->anything()
        );
    }

    public function testAuthorizePassesFalseAsSessionValue()
    {
        $this->authService->authorize(null);
        Phake::verify($this->apiService)->auth(
            $this->anything(),
            false,
            $this->anything(),
            $this->anything(),
            $this->anything()
        );
    }

    public function testAuthorizeTriggersAuthRequestEvent()
    {
        $this->authService->authorize(null);
        $event = $name = null;
        Phake::verify($this->eventDispatcher)->dispatchEvent(Phake::capture($name), Phake::capture($event));
        $this->assertEquals(AuthRequestEvent::NAME, $name, "Unexpected Name");
        $this->assertInstanceOf('\LaunchKey\SDK\Event\AuthRequestEvent', $event, "Unexpected event");
        $this->assertSame($this->authRequest, $event->getAuthRequest());
    }

    public function testAuthorizeBubblesErrorsFromTheApiService()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        Phake::when($this->apiService)->auth(Phake::anyParameters())->thenThrow(new CommunicationError());
        $this->authService->authorize(null);
    }

    public function testAuthorizeBubblesErrorsFromThePingService()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        Phake::when($this->pingService)->ping(Phake::anyParameters())->thenThrow(new CommunicationError());
        $this->authService->authorize(null);
    }

    public function testAuthorizeDebugLogsWhenLoggerPresent()
    {
        $this->loggingAuthService->authorize(null);
        Phake::verify($this->logger, Phake::atLeast(1))->debug(Phake::anyParameters());
    }

    public function testAuthenticateCallsPingService()
    {
        $this->authService->authenticate(null);
        Phake::verify($this->pingService)->ping();
    }

    public function testAuthenticatePassesPublicKeyFromPingResponseToApiServiceAuth()
    {
        $this->authService->authenticate(null);
        Phake::verify($this->apiService)->auth(
            $this->anything(),
            $this->anything(),
            $this->anything(),
            $this->anything(),
            $this->pingResponse->getPublicKey()
        );
   }

    public function testAuthenticatePassesAppKeyToApiServiceAuth()
    {
        $this->authService->authenticate("username");
        Phake::verify($this->apiService)->auth(
            $this->anything(),
            $this->anything(),
            "APP KEY",
            $this->anything(),
            $this->anything()
        );
    }

    public function testAuthenticatePassesSecretKeyToApiServiceAuth()
    {
        $this->authService->authenticate("username");
        Phake::verify($this->apiService)->auth(
            $this->anything(),
            $this->anything(),
            $this->anything(),
            "SECRET KEY",
            $this->anything()
        );
    }

    public function testAuthenticatePassesUsernameToApiServiceAuth()
    {
        $this->authService->authenticate("username");
        Phake::verify($this->apiService)->auth(
            "username",
            $this->anything(),
            $this->anything(),
            $this->anything(),
            $this->anything()
        );
    }

    public function testAuthenticatePassesTrueAsSessionValueToApiServiceAuth()
    {
        $this->authService->authenticate(null);
        Phake::verify($this->apiService)->auth(
            $this->anything(),
            true,
            $this->anything(),
            $this->anything(),
            $this->anything()
        );
    }

    public function testAuthenticateTriggersAuthRequestEvent()
    {
        $this->authService->authenticate(null);
        $event = $name = null;
        Phake::verify($this->eventDispatcher)->dispatchEvent(Phake::capture($name), Phake::capture($event));
        $this->assertEquals(AuthRequestEvent::NAME, $name, "Unexpected Name");
        $this->assertInstanceOf('LaunchKey\SDK\Event\AuthRequestEvent', $event, "Unexpected event");
        $this->assertSame($this->authRequest, $event->getAuthRequest());
    }

    public function testAuthenticateBubblesErrorsFromApiServiceAuth()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        Phake::when($this->apiService)->auth(Phake::anyParameters())->thenThrow(new CommunicationError());
        $this->authService->authenticate(null);
    }

    public function testAuthenticateBubblesErrorsFromThePingService()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        Phake::when($this->pingService)->ping(Phake::anyParameters())->thenThrow(new CommunicationError());
        $this->authService->authenticate(null);
    }

    public function testAuthenticateDebugLogsWhenLoggerPresent()
    {
        $this->loggingAuthService->authenticate(null);
        Phake::verify($this->logger, Phake::atLeast(1))->debug(Phake::anyParameters());
    }

    public function testGetStatusCallsPingService()
    {
        $this->authService->getStatus(null);
        Phake::verify($this->pingService)->ping();
    }

    public function testGetStatusPassesPublicKeyFromPingResponseToApiServicePoll()
    {
        $this->authService->getStatus(null);
        Phake::verify($this->apiService)->poll(
            $this->anything(),
            $this->pingResponse->getPublicKey()
        );
    }

    public function testGetStatusPassesUsernameToApiServicePoll()
    {
        $this->authService->getStatus("auth request");
        Phake::verify($this->apiService)->poll(
            "auth request",
            $this->anything()
        );
    }

    public function testGetStatusTriggersAuthResponseEvent()
    {
        $this->authService->getStatus(null);
        $event = $name = null;
        Phake::verify($this->eventDispatcher)->dispatchEvent(Phake::capture($name), Phake::capture($event));
        $this->assertEquals(AuthResponseEvent::NAME, $name, "Unexpected Name");
        $this->assertInstanceOf('\LaunchKey\SDK\Event\AuthResponseEvent', $event, "Unexpected event");
        $this->assertSame($this->authResponse, $event->getAuthResponse());
    }

    public function testGetStatusBubblesErrorsFromTheApiService()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        Phake::when($this->apiService)->poll(Phake::anyParameters())->thenThrow(new CommunicationError());
        $this->authService->getStatus(null);
    }

    public function testGetStatusBubblesErrorsFromThePingService()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        Phake::when($this->pingService)->ping(Phake::anyParameters())->thenThrow(new CommunicationError());
        $this->authService->getStatus(null);
    }

    public function testGetStatusDebugLogsWhenLoggerPresent()
    {
        $this->loggingAuthService->getStatus(null);
        Phake::verify($this->logger, Phake::atLeast(1))->debug(Phake::anyParameters());
    }

    public function testGetStatusDoesNotUpdatesApiViaLogsWhenPending()
    {
        $this->authService->getStatus(null);
        phake::verify($this->apiService, Phake::never())->log(Phake::anyParameters());
    }

    public function testGetStatusDoesNotUpdatesApiViaLogsWhenResponseIsFalse()
    {
        $this->authResponse = new AuthResponse(null, null, null, null, true, false);
        $this->authService->getStatus(null);
        phake::verify($this->apiService, Phake::never())->log(Phake::anyParameters());
    }

    public function testGetStatusUpdatesApiViaLogsWithAuthenticateTrueWhenResponseIsTrue()
    {
        $authResponse = new AuthResponse("auth request authRequestId", null, null, null, true, true);
        Phake::when($this->apiService)->poll(Phake::anyParameters())->thenReturn($authResponse);
        $this->authService->getStatus("auth request authRequestId");
        phake::verify($this->apiService)
            ->log("auth request authRequestId", "Authenticate", true, $this->pingResponse->getPublicKey());
    }

    public function testGetStatusDoesNotBubbleErrorsFromApiServiceLogsRequest()
    {
        Phake::when($this->apiService)->log(Phake::anyParameters())->thenThrow(new CommunicationError());
        $this->authResponse = new AuthResponse(null, null, null, null, true, true);
        $this->authService->getStatus(null);
    }

    public function testGetStatusLogsErrorsFromApiServiceLogsRequestWhenLoggerPresent()
    {
        $authResponse = new AuthResponse("auth request authRequestId", null, null, null, true, true);
        Phake::when($this->apiService)->poll(Phake::anyParameters())->thenReturn($authResponse);
        Phake::when($this->apiService)->log(Phake::anyParameters())->thenThrow(new CommunicationError());
        $this->authResponse = new AuthResponse(null, null, null, null, true, true);
        $this->loggingAuthService->getStatus(null);
        Phake::verify($this->logger)->error(Phake::anyParameters());
    }

    public function testDeOrbitCallsPingService()
    {
        $this->authService->deOrbit(null);
        Phake::verify($this->pingService)->ping();
    }

    public function testDeOrbitPassesPublicKeyFromPingResponseToApiServiceLogsRequest()
    {
        $this->authService->deOrbit(null);
        Phake::verify($this->apiService)->log(
            $this->anything(),
            $this->anything(),
            $this->anything(),
            $this->pingResponse->getPublicKey()
        );
    }

    public function testDeOrbitPassesAuthRequestIdToApiServiceLogsRequest()
    {
        $this->authService->deOrbit("expected authRequestId");
        Phake::verify($this->apiService)->log(
            "expected authRequestId",
            $this->anything(),
            $this->anything(),
            $this->anything()
        );
    }

    public function testDeOrbitPassesRevokeActionToApiServiceLogsRequest()
    {
        $this->authService->deOrbit(null);
        Phake::verify($this->apiService)->log(
            $this->anything(),
            "Revoke",
            $this->anything(),
            $this->anything()
        );
    }

    public function testDeOrbitPassesStatusTrueToApiServiceLogsRequest()
    {
        $this->authService->deOrbit(null);
        Phake::verify($this->apiService)->log(
            $this->anything(),
            $this->anything(),
            true,
            $this->anything()
        );
    }

    public function testDeOrbitTriggersDeOrbitRequestEvent()
    {
        $this->authService->deOrbit("expected authRequestId");
        $event = $name = null;
        Phake::verify($this->eventDispatcher)->dispatchEvent(Phake::capture($name), Phake::capture($event));
        $this->assertEquals(DeOrbitRequestEvent::NAME, $name, "Unexpected Name");
        $this->assertInstanceOf('LaunchKey\SDK\Event\DeOrbitRequestEvent', $event, "Unexpected event");
        $this->assertEquals("expected authRequestId", $event->getDeOrbitRequest()->getAuthRequestId());
    }

    public function testDeOrbitBubblesErrorsFromTheApiService()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        Phake::when($this->apiService)->log(Phake::anyParameters())->thenThrow(new CommunicationError());
        $this->authService->deOrbit(null);
    }

    public function testDeOrbitBubblesErrorsFromThePingService()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        Phake::when($this->pingService)->ping(Phake::anyParameters())->thenThrow(new CommunicationError());
        $this->authService->deOrbit(null);
    }

    public function testDeOrbitDebugLogsWhenLoggerPresent()
    {
        $this->loggingAuthService->deOrbit(null);
        Phake::verify($this->logger, Phake::atLeast(1))->debug(Phake::anyParameters());
    }

    public function testHandleCallbackBubblesErrors()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\UnknownCallbackActionError');
        Phake::when($this->apiService)
            ->handleCallback(Phake::anyParameters())
            ->thenThrow(new UnknownCallbackActionError());
        $this->authService->handleCallback(array());
    }

    public function testHandleCallbackTriggersCorrectAuthResponseEventWhenApiServiceReturnsAnAuthResponse()
    {
        Phake::when($this->apiService)->handleCallback(Phake::anyParameters())->thenReturn($this->authResponse);
        $this->authService->handleCallback(array());
        Phake::verify($this->eventDispatcher)->dispatchEvent(Phake::capture($name), Phake::capture($event));
        $this->assertEquals(AuthResponseEvent::NAME, $name, "Unexpected Name");
        $this->assertInstanceOf('LaunchKey\SDK\Event\AuthResponseEvent', $event, "Unexpected event");
        $this->assertSame($this->authResponse, $event->getAuthResponse());
    }

    public function testHandleCallbackTriggersCorrectDeOrbitCallbackEventForValidDeOrbitCallback()
    {
        $deOrbitCallback = new DeOrbitCallback(new \DateTime("-10 minutes"), null);
        Phake::when($this->apiService)->handleCallback(Phake::anyParameters())->thenReturn($deOrbitCallback);
        $this->authService->handleCallback(array());
        Phake::verify($this->eventDispatcher)->dispatchEvent(Phake::capture($name), Phake::capture($event));
        $this->assertEquals(DeOrbitCallbackEvent::NAME, $name, "Unexpected Name");
        $this->assertInstanceOf('LaunchKey\SDK\Event\DeOrbitCallbackEvent', $event, "Unexpected event");
        $this->assertSame($deOrbitCallback, $event->getDeOrbitCallback());
    }

    public function testHandleCallbackLogsWithAuthenticateTrueWhenResponseIsTrue()
    {
        $authResponse = new AuthResponse("auth request authRequestId", null, null, null, true, true);
        Phake::when($this->apiService)->handleCallback(Phake::anyParameters())->thenReturn($authResponse);
        $this->authService->handleCallback(array());
        phake::verify($this->apiService)
            ->log("auth request authRequestId", "Authenticate", true, $this->pingResponse->getPublicKey());
    }

    protected function setUp()
    {
        Phake::initAnnotations($this);
        $this->authService = new BasicAuthService(
            "APP KEY",
            "SECRET KEY",
            $this->apiService,
            $this->pingService,
            $this->eventDispatcher
        );
        $this->loggingAuthService = new BasicAuthService(
            "APP KEY",
            "SECRET KEY",
            $this->apiService,
            $this->pingService,
            $this->eventDispatcher,
            $this->logger
        );

        Phake::when($this->apiService)->auth(Phake::anyParameters())->thenReturn($this->authRequest);

        $this->pingResponse = new PingResponse(new \DateTime("-10 minutes"), "PUBIC KEY", new \DateTime("-20 minutes"));
        Phake::when($this->pingService)->ping(Phake::anyParameters())->thenReturn($this->pingResponse);

        $this->authResponse = new AuthResponse("authRequestId", "user hash", null, "user push authRequestId");
        Phake::when($this->apiService)->poll(Phake::anyParameters())->thenReturn($this->authResponse);
    }

    protected function tearDown()
    {
        $this->authService = null;
        $this->loggingAuthService = null;
        $this->apiService = null;
        $this->pingService = null;
        $this->pingResponse = null;
        $this->authResponse = null;
        $this->authRequest = null;
        $this->eventDispatcher = null;
        $this->logger = null;
    }
}
