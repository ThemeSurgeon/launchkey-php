<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;

use Guzzle\Http\ClientInterface;
use LaunchKey\SDK\Cache\Cache;
use LaunchKey\SDK\Domain\AuthResponse;
use LaunchKey\SDK\Domain\DeOrbitCallback;
use LaunchKey\SDK\Service\CryptService;
use LaunchKey\SDK\Service\GuzzleApiService;

class GuzzleApiServiceHandleCallbackTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GuzzleApiService
     */
    private $apiService;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @Mock
     * @var ClientInterface
     */
    private $guzzleClient;

    /**
     * @Mock
     * @var CryptService
     */
    private $cryptService;

    /**
     * @Mock
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $publicKey;

    public function testThrowsUnknownCallbackActionErrorWhenNotAuthOrDeOrbit()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\UnknownCallbackActionError');
        $this->apiService->handleCallback(array());
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
        $response = $this->apiService->handleCallback($data);
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
        $response = $this->apiService->handleCallback($data);
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
        $response = $this->apiService->handleCallback($data);
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
        $this->apiService->handleCallback($data);
    }

    public function testWhenPostDataIsForDeOrbitYouReceiveDeOrbitRequest()
    {
        $data = array(
            "deorbit" => "{\"user_hash\": \"User Hash\", \"launchkey_time\": \"2010-01-01 00:00:00\"}",
            "signature" => "Signature"
        );
        $response = $this->apiService->handleCallback($data);
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
        $this->apiService->handleCallback($data);
    }

    public function testWhenPostDataIsForDeOrbitTheSignatureVerificationIsAgainstTheDeOrbitData()
    {
        $deOrbit = "{\"user_hash\": \"User Hash\", \"launchkey_time\": \"2010-01-01 00:00:00\"}";
        $signature = "Signature";
        $data = array("deorbit" => $deOrbit, "signature" => $signature);
        $this->apiService->handleCallback($data);
        \Phake::verify($this->cryptService)->verifySignature($signature, $deOrbit, $this->publicKey, false);
    }


    protected function setUp()
    {
        \Phake::initAnnotations($this);
        $this->apiService = new GuzzleApiService(
            null,
            $this->secretKey,
            $this->guzzleClient,
            $this->cryptService,
            $this->cache,
            null
        );
        \Phake::when($this->cryptService)->decryptRSA()->thenReturn(
            '{ "device_id": "Device ID", "response": "true", "auth_request": "Auth Request", "app_pins": "APP,PINS"}'
        );
        \Phake::when($this->cryptService)->verifySignature(\Phake::anyParameters())->thenReturn(true);

        $this->publicKey = "PUBLIC KEY";
        \Phake::when($this->cache)->get(GuzzleApiService::CACHE_KEY_PUBLIC_KEY)->thenReturn($this->publicKey);
    }

    protected function tearDown()
    {
        $this->secretKey = null;
        $this->guzzleClient = null;
        $this->cryptService = null;
        $this->cache = null;
        $this->apiService = null;
        $this->publicKey = null;
    }
}
