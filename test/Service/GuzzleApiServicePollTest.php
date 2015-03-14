<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;

use LaunchKey\SDK\Domain\AuthResponse;

class GuzzleApiServicePollTest extends GuzzleApiServiceTestAbstract
{
    public function testPollIsGetRequest()
    {
        $this->apiService->poll(null);
        $this->assertEquals("GET", $this->getGuzzleRequest()->getMethod());
    }

    public function testPollUsesCorrectRelativePath()
    {
        $this->apiService->poll(null);
        $this->assertEquals("/poll", $this->getGuzzleRequest()->getPath());
    }

    public function testPollSendsAppKeyInFormData()
    {
        $this->apiService->poll(null);
        $this->assertGuzzleRequestFormFieldValueEquals("app_key", $this->appKey);
    }

    public function testPollSendsEncryptedSecretKeyInFormData()
    {
        $this->apiService->poll(null);
        $this->assertGuzzleRequestFormFieldValueEquals("secret_key", base64_encode($this->rsaEncrypted));
    }

    public function testPollSendsSignatureInFormData()
    {
        $this->apiService->poll(null);
        $this->assertGuzzleRequestFormFieldValueEquals("signature", $this->signed);
    }

    public function testPollSendsAuthRequestInFormData()
    {
        $this->apiService->poll("Auth Request");
        $this->assertGuzzleRequestFormFieldValueEquals("auth_request", "Auth Request");
    }

    public function testPollEncryptedCorrectDataForSecretKey()
    {
        $this->apiService->poll(null);
        $this->assertLastItemEncryptedWasValidSecretKey();
    }

    public function testPollSignedTheEncryptedSecretKey()
    {
        $this->apiService->poll(null);
        \Phake::verify($this->cryptService)->sign($this->rsaEncrypted);
    }

    public function testPollReturnsAuthResponseForPendingResponse()
    {
        $this->setFixtureResponse("api_responses/poll/pending.txt");
        $response = $this->apiService->poll(null);
        $this->assertInstanceOf('\LaunchKey\SDK\Domain\AuthResponse', $response);
        return $response;
    }

    /**
     * @depends testPollReturnsAuthResponseForPendingResponse
     * @param AuthResponse $authResponse
     */
    public function testPollSetsAuthRequestIdNullInAuthResponseForPendingResponse(AuthResponse $authResponse)
    {
        $this->assertNull($authResponse->getAuthRequestId());
    }

    /**
     * @depends testPollReturnsAuthResponseForPendingResponse
     * @param AuthResponse $authResponse
     */
    public function testPollSetsCompletedFalseInAuthResponseForPendingResponse(AuthResponse $authResponse)
    {
        $this->assertFalse($authResponse->isCompleted());
    }

    /**
     * @depends testPollReturnsAuthResponseForPendingResponse
     * @param AuthResponse $authResponse
     */
    public function testPollSetsAuthorizedNullInAuthResponseForPendingResponse(AuthResponse $authResponse)
    {
        $this->assertNull($authResponse->isAuthorized());
    }

    /**
     * @depends testPollReturnsAuthResponseForPendingResponse
     * @param AuthResponse $authResponse
     */
    public function testPollDoesNotSetUserHashNullInAuthResponseForPendingResponse(AuthResponse $authResponse)
    {
        $this->assertNull($authResponse->getUserHash());
    }

    /**
     * @depends testPollReturnsAuthResponseForPendingResponse
     * @param AuthResponse $authResponse
     */
    public function testPollSetsOrganizationUserIdNullInAuthResponseForPendingResponse(AuthResponse $authResponse)
    {
        $this->assertNull( $authResponse->getOrganizationUserId());
    }

    /**
     * @depends testPollReturnsAuthResponseForPendingResponse
     * @param AuthResponse $authResponse
     */
    public function testPollSetsUserPushIdNullInAuthResponseForPendingResponse(AuthResponse $authResponse)
    {
        $this->assertNull($authResponse->getUserPushId());
    }

    public function testPollDecryptsAuthOnOkayResponse()
    {
        $this->setFixtureResponse("api_responses/poll/ok.txt");
        $this->apiService->poll(null);
        \Phake::verify($this->cryptService)->decryptRSA("Encrypted Auth");
    }

    public function testPollReturnsAuthResponseForOkayResponse()
    {
        $this->setFixtureResponse("api_responses/poll/ok.txt");
        $response = $this->apiService->poll(null);
        $this->assertInstanceOf('\LaunchKey\SDK\Domain\AuthResponse', $response);
        return $response;
    }

    /**
     * @depends testPollReturnsAuthResponseForOkayResponse
     * @param AuthResponse $authResponse
     */
    public function testPollSetsAuthRequestIdInAuthResponseForOkResponse(AuthResponse $authResponse)
    {
        $this->assertEquals("Auth Request", $authResponse->getAuthRequestId());
    }

    /**
     * @depends testPollReturnsAuthResponseForOkayResponse
     * @param AuthResponse $authResponse
     */
    public function testPollSetsCompletedTrueInAuthResponseForOkResponse(AuthResponse $authResponse)
    {
        $this->assertTrue($authResponse->isCompleted());
    }

    /**
     * @depends testPollReturnsAuthResponseForOkayResponse
     * @param AuthResponse $authResponse
     */
    public function testPollSetsAuthorizedTrueInAuthResponseForOkResponse(AuthResponse $authResponse)
    {
        $this->assertTrue($authResponse->isAuthorized());
    }

    /**
     * @depends testPollReturnsAuthResponseForOkayResponse
     * @param AuthResponse $authResponse
     */
    public function testPollSetsUserHashInAuthResponseForOkResponse(AuthResponse $authResponse)
    {
        $this->assertEquals("User Hash", $authResponse->getUserHash());
    }

    /**
     * @depends testPollReturnsAuthResponseForOkayResponse
     * @param AuthResponse $authResponse
     */
    public function testPollSetsOrganizationUserIdInAuthResponseForOkResponse(AuthResponse $authResponse)
    {
        $this->assertEquals("Org User", $authResponse->getOrganizationUserId());
    }

    /**
     * @depends testPollReturnsAuthResponseForOkayResponse
     * @param AuthResponse $authResponse
     */
    public function testPollSetsUserPushIdInAuthResponseForOkResponse(AuthResponse $authResponse)
    {
        $this->assertEquals("User Push Id", $authResponse->getUserPushId());
    }

    /**
     * @depends testPollReturnsAuthResponseForOkayResponse
     * @param AuthResponse $authResponse
     */
    public function testPollSetsDeviceIdInAuthResponseForOkResponse(AuthResponse $authResponse)
    {
        $this->assertEquals("Device ID", $authResponse->getDeviceId());
    }

    public function testPollThrowsInvalidResponseErrorWhenBodyIsNotParseable()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidResponseError');
        $this->setFixtureResponse("api_responses/invalid.txt");
        $this->apiService->poll(null);
    }


    public function testPollThrowsCommunicationErrorOnServerError()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        $this->setFixtureResponse("api_responses/server_error.txt");
        $this->apiService->poll(null);
    }

    public function testPollThrowsInvalidRequestOn400()
    {
        $this->setExpectedException(
            '\LaunchKey\SDK\Service\Exception\InvalidRequestError',
            '{"username":"Invalid character used. Do not use &gt; &lt; ) ( @ : ; &amp;"}',
            40421
        );
        $this->setFixtureResponse("api_responses/request_error.txt");
        $this->apiService->poll(null);
    }

    protected function setUp()
    {
        parent::setUp();
        $this->setFixtureResponse("api_responses/poll/ok.txt");
        \Phake::when($this->cryptService)->decryptRSA(\Phake::anyParameters())->thenReturn(
            '{ "device_id": "Device ID", "response": "true", "auth_request": "Auth Request", "app_pins": "APP,PINS"}'
        );
    }
}
