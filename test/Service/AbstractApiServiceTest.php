<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use LaunchKey\SDK\Cache\Cache;
use LaunchKey\SDK\Domain\AuthResponse;
use LaunchKey\SDK\Domain\DeOrbitCallback;
use LaunchKey\SDK\Domain\PingResponse;
use LaunchKey\SDK\Service\AbstractApiService;
use LaunchKey\SDK\Service\CryptService;
use LaunchKey\SDK\Service\GuzzleApiService;
use Phake;
use Psr\Log\LoggerInterface;

class AbstractApiServiceTest extends \PHPUnit_Framework_TestCase
{
    private $pingPublicKey = "Expected Ping PublicKey";
    /**
     * @var int
     */
    private $ttl = 9999;
    /**
     * @Mock
     * @var Cache
     */
    private $cache;

    /**
     * @var GetPublicKeyVisible
     */
    private $api;

    /**
     * @var GetPublicKeyVisible
     */
    private $loggingApi;

    /**
     * @Mock
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @Mock
     * @var CryptService
     */
    private $cryptService;

    /**
     * @var string
     */
    private $secretKey = 'expected_secret_key';

    /**
     * @var string
     */
    private $publicKey;

    public function testGetPublicKeyReturnsCachedVersionWhenInCache()
    {
        Phake::when($this->cache)
            ->get(AbstractApiService::CACHE_KEY_PUBLIC_KEY)
            ->thenReturn("Expected");
        $this->assertEquals("Expected", $this->api->getKey());
    }

    public function testGetPublicKeyDoesNotCallPingWhenInCache()
    {
        Phake::when($this->cache)
            ->get(AbstractApiService::CACHE_KEY_PUBLIC_KEY)
            ->thenReturn("Expected");
        $this->api->getKey();
        Phake::verify($this->api, Phake::never())->ping(Phake::anyParameters());
    }

    public function testGetPublicKeyDoesSetCacheWhenInCache()
    {
        Phake::when($this->cache)
            ->get(AbstractApiService::CACHE_KEY_PUBLIC_KEY)
            ->thenReturn("Expected");
        $this->api->getKey();
        Phake::verify($this->cache, Phake::never())->set(Phake::anyParameters());
    }

    public function testGetPublicKeyReturnsPingResponseKeyWhenNotInCache()
    {
        $this->assertEquals($this->pingPublicKey, $this->api->getKey());
    }

    public function testGetPublicKeyCachesPingResponseKeyWithProperTTLWhenNotInCache()
    {
        $this->api->getKey();
        Phake::verify($this->cache)->set(
            AbstractApiService::CACHE_KEY_PUBLIC_KEY,
            $this->pingPublicKey,
            $this->ttl
        );
    }

    public function testServiceDebugLogsWhenLoggerIsPresent()
    {
        $this->loggingApi->getKey();
        Phake::verify($this->logger, Phake::atLeast(1))->debug(\Phake::anyParameters());
    }

    public function testServiceDoesNotErrLogWhenLoggerIsPresentButNoErrors()
    {
        $this->loggingApi->getKey();
        Phake::verify($this->logger, Phake::never())->errir(\Phake::anyParameters());
    }

    public function testServiceDoesNotErrorWhenCacheGetErrorsButReturnsPingPublicKey()
    {
        Phake::when($this->cache)->get(Phake::anyParameters())->thenThrow(new \Exception());
        $this->assertEquals($this->pingPublicKey, $this->api->getKey());
    }

    public function testServiceLogsErrorWhenCacheGetErrors()
    {
        Phake::when($this->cache)->get(Phake::anyParameters())->thenThrow(new \Exception());
        $this->loggingApi->getKey();
        Phake::verify($this->logger, Phake::atLeast(1))->error(Phake::anyParameters());
    }

    public function testServiceDoesNotErrorWhenCacheSetErrorsButReturnsPingPublicKey()
    {
        Phake::when($this->cache)->set(Phake::anyParameters())->thenThrow(new \Exception());
        $this->assertEquals($this->pingPublicKey, $this->api->getKey());
    }

    public function testServiceLogsErrorWhenCacheSetErrors()
    {
        Phake::when($this->cache)->set(Phake::anyParameters())->thenThrow(new \Exception());
        $this->loggingApi->getKey();
        Phake::verify($this->logger, Phake::atLeast(1))->error(Phake::anyParameters());
    }

    public function testThrowsUnknownCallbackActionErrorWhenNotAuthOrDeOrbit()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\UnknownCallbackActionError');
        $this->api->handleCallback(array());
    }

    public function testWhenPostDataIsForAuthYouReceiveAuthResponse()
    {
        $data = array(
            "auth" => "Encrypted Auth",
            "user_hash" => "User Hash",
            "auth_request" => "Auth Request",
            "organization_user" => "Organization User",
            "user_push_id" => "User Push ID"
        );
        \Phake::when($this->cryptService)->decryptRSA(\Phake::anyParameters())->thenReturn(
            '{ "device_id": "Device ID", "response": "true", "auth_request": "' . $data["auth_request"] . '", "app_pins": "APP,PINS"}'
        );
        $response = $this->api->handleCallback($data);
        $this->assertInstanceOf('\LaunchKey\SDK\Domain\AuthResponse', $response);
        return $response;
    }

    /**
     * @depends testWhenPostDataIsForAuthYouReceiveAuthResponse
     */
    public function testWhenPostDataIsForAuthTheAuthResponseHasTheCorrectAuthRequestId(AuthResponse $authResponse)
    {
        $this->assertEquals("Auth Request", $authResponse->getAuthRequestId());
    }

    /**
     * @depends testWhenPostDataIsForAuthYouReceiveAuthResponse
     */
    public function testWhenPostDataIsForAuthTheAuthResponseHasTheCorrectUserHash(AuthResponse $authResponse)
    {
        $this->assertEquals("User Hash", $authResponse->getUserHash());
    }

    /**
     * @depends testWhenPostDataIsForAuthYouReceiveAuthResponse
     */
    public function testWhenPostDataIsForAuthTheAuthResponseHasTheCorrectOrganizationUserId(AuthResponse $authResponse)
    {
        $this->assertEquals("Organization User", $authResponse->getOrganizationUserId());
    }

    /**
     * @depends testWhenPostDataIsForAuthYouReceiveAuthResponse
     */
    public function testWhenPostDataIsForAuthTheAuthResponseHasTheCorrectUserPushId(AuthResponse $authResponse)
    {
        $this->assertEquals("User Push ID", $authResponse->getUserPushId());
    }

    /**
     * @depends testWhenPostDataIsForAuthYouReceiveAuthResponse
     */
    public function testWhenPostDataIsForAuthTheAuthResponseHasTheCorrectDeviceId(AuthResponse $authResponse)
    {
        $this->assertEquals("Device ID", $authResponse->getDeviceId());
    }

    /**
     * @depends testWhenPostDataIsForAuthYouReceiveAuthResponse
     */
    public function testWhenPostDataIsForAuthTheAuthResponseDefaultsOrganizationUserIdToNull()
    {
        $data = array(
            "auth" => "Encrypted Auth",
            "user_hash" => "User Hash",
            "auth_request" => "Auth Request",
            "user_push_id" => "User Push ID"
        );
        \Phake::when($this->cryptService)->decryptRSA(\Phake::anyParameters())->thenReturn(
            '{ "device_id": "Device ID", "response": "true", "auth_request": "' . $data["auth_request"] . '"}'
        );
        $response = $this->api->handleCallback($data);
        $this->assertNull($response->getOrganizationUserId());
    }

    /**
     * @depends testWhenPostDataIsForAuthYouReceiveAuthResponse
     */
    public function testWhenPostDataIsForAuthTheAuthResponseHasAuthorizedTrueWhenResponseIsTrue()
    {
        $data = array(
            "auth" => "Encrypted Auth",
            "user_hash" => "User Hash",
            "auth_request" => "Auth Request",
            "user_push_id" => "User Push ID"
        );
        \Phake::when($this->cryptService)->decryptRSA(\Phake::anyParameters())->thenReturn(
            '{ "device_id": "Device ID", "response": "true", "auth_request": "' . $data["auth_request"] . '"}'
        );
    }

    /**
     * @depends testWhenPostDataIsForAuthYouReceiveAuthResponse
     */
    public function testWhenPostDataIsForAuthTheAuthResponseHasAuthorizedTrueWhenResponseIsFalse()
    {
        $data = array(
            "auth" => "Encrypted Auth",
            "user_hash" => "User Hash",
            "auth_request" => "Auth Request",
            "user_push_id" => "User Push ID"
        );
        \Phake::when($this->cryptService)->decryptRSA(\Phake::anyParameters())->thenReturn(
            '{ "device_id": "Device ID", "response": "false", "auth_request": "' . $data["auth_request"] . '"}'
        );
        $response = $this->api->handleCallback($data);
        $this->assertFalse($response->isAuthorized());
    }

    /**
     * @depends testWhenPostDataIsForAuthYouReceiveAuthResponse
     */
    public function testWhenPostDataIsForAuthInvalidResponseErrorIsThrownWhenAuthRequestsDontMatch()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidRequestError');
        \Phake::when($this->cryptService)->decryptRSA()->thenReturn(
            '{ "device_id": "Device ID", "response": "true", "auth_request": "Auth Request"}'
        );
        $data = array(
            "auth" => "Encrypted Auth",
            "user_hash" => "User Hash",
            "auth_request" => "Other Auth Request",
            "user_push_id" => "USer Push ID"
        );
        $this->api->handleCallback($data);
    }

    public function testWhenPostDataIsForDeOrbitYouReceiveDeOrbitRequest()
    {
        $data = array(
            "deorbit" => "{\"user_hash\": \"User Hash\", \"launchkey_time\": \"2010-01-01 00:00:00\"}",
            "signature" => "Signature"
        );
        $response = $this->api->handleCallback($data);
        $this->assertInstanceOf('\LaunchKey\SDK\Domain\DeOrbitCallback', $response);
        return $response;
    }

    /** @depends testWhenPostDataIsForDeOrbitYouReceiveDeOrbitRequest */
    public function testWhenPostDataIsForDeOrbitYouReceiveCorrectDeOrbitTime(DeOrbitCallback $callback)
    {
        $expected = \DateTime::createFromFormat(
            GuzzleApiService::LAUNCHKEY_DATE_FORMAT,
            "2010-01-01 00:00:00",
            new \DateTimeZone("UTC")
        );
        $this->assertEquals($expected, $callback->getDeOrbitTime());
    }

    /** @depends testWhenPostDataIsForDeOrbitYouReceiveDeOrbitRequest */
    public function testWhenPostDataIsForDeOrbitYouReceiveCorrectUserHash(DeOrbitCallback $callback)
    {
        $this->assertEquals("User Hash", $callback->getUserHash());
    }

    public function testWhenPostDataIsForDeOrbitInvalidRequestErrorIsThrownWhenSignatureDoesNotMatch()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidRequestError');
        \Phake::when($this->cryptService)->verifySignature(\Phake::anyParameters())->thenReturn(false);
        $data = array("deorbit" => "deOrbitData",  "signature" => "Signature");
        $this->api->handleCallback($data);
    }

    public function testWhenPostDataIsForDeOrbitTheSignatureVerificationIsAgainstTheDeOrbitData()
    {
        $deOrbit = "{\"user_hash\": \"User Hash\", \"launchkey_time\": \"2010-01-01 00:00:00\"}";
        $signature = "Signature";
        $data = array("deorbit" => $deOrbit, "signature" => $signature);
        $this->api->handleCallback($data);
        \Phake::verify($this->cryptService)->verifySignature($signature, $deOrbit, $this->pingPublicKey, false);
    }

    protected function setUp()
    {
        Phake::initAnnotations($this);
        $this->api = Phake::partialMock(
            'LaunchKey\SDK\Test\Service\GetPublicKeyVisible',
            $this->cache,
            $this->cryptService,
            $this->secretKey,
            $this->ttl
        );
        $this->loggingApi = Phake::partialMock(
            'LaunchKey\SDK\Test\Service\GetPublicKeyVisible',
            $this->cache,
            $this->cryptService,
            $this->secretKey,
            $this->ttl,
            $this->logger
        );
        $pingResponse = new PingResponse(new \DateTime(), $this->pingPublicKey, new \DateTime());
        Phake::when($this->api)->ping()->thenReturn($pingResponse);
        Phake::when($this->loggingApi)->ping()->thenReturn($pingResponse);

        \Phake::when($this->cryptService)->decryptRSA()->thenReturn(
            '{ "device_id": "Device ID", "response": "true", "auth_request": "Auth Request", "app_pins": "APP,PINS"}'
        );
        \Phake::when($this->cryptService)->verifySignature(\Phake::anyParameters())->thenReturn(true);

    }

    protected function tearDown()
    {
        $this->api = null;
        $this->loggingApi = null;
        $this->cache = null;
    }
}

abstract class GetPublicKeyVisible extends AbstractApiService
{
    public function getKey()
    {
        return $this->getPublicKey();
    }
}
