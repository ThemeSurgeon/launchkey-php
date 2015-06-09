<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use LaunchKey\SDK\Domain\AuthRequest;
use LaunchKey\SDK\Domain\AuthResponse;
use Phake;

class WordPressApiServicePollTest extends WordPressApiServiceTestAbstract
{
    protected function setUp()
    {
        parent::setUp();
        $this->response = array(
            'headers' => array(
                'Server' => 'nginx',
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Length' => '151',
                'Connection' => 'close',
                'Date' => 'Thu, 12 Mar 2015 16:55:12 GMT'

            ),
            'body' => '{"organization_user": "Org User", "user_push_id": "User Push Id", "user_hash": "User Hash", "auth": "Encrypted Auth"}',
            'response' => array('code' => 200, 'message' => 'OK'),
            'cookies' => array(),
            'filename' => null
        );
        $this->pendingResponse = array(
            'headers' => array(
                'Server' => 'nginx',
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Length' => '111',
                'Connection' => 'close',
                'Date' => 'Thu, 12 Mar 2015 16:55:12 GMT'

            ),
            'body' => '{"successful": false, "status_code": 400, "message": "Pending response", "message_code": 70403, "response": ""}',
            'response' => array('code' => 400, 'message' => 'Bad Request'),
            'cookies' => array(),
            'filename' => null
        );

        \Phake::when($this->cryptService)->decryptRSA(\Phake::anyParameters())->thenReturn(
            '{ "device_id": "Device ID", "response": "true", "auth_request": "Auth Request", "app_pins": "APP,PINS"}'
        );
    }

    public function testCallsRequest()
    {
        $this->apiService->poll("AUTH_REQUEST");
        Phake::verify($this->client)->request($this->anything(), Phake::capture($options));
        return $options;
    }

    /**
     * @depends testCallsRequest
     */
    public function testRequestUsesCorrectURL()
    {
        $this->apiService->poll(null);
        Phake::verify($this->client)->request('https://api.base.url/v1/poll?METHOD=GET', $this->anything());
    }

    /**
     * @depends testCallsRequest
     * @param array $options
     */
    public function testRequestMethodIsPOST(array $options)
    {
        $this->assertEquals('POST', $options['method']);
    }

    /**
     * @depends testCallsRequest
     * @param array $options
     */
    public function testRequestSendsHeaders(array $options)
    {
        $this->assertArrayHasKey('headers', $options);
        return $options['headers'];
    }

    /**
     * @depends testRequestSendsHeaders
     * @param array $headers
     */
    public function testRequestAcceptIsApplicationJson(array $headers)
    {
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertEquals('application/json', $headers['Accept']);
    }

    /**
     * @depends testRequestSendsHeaders
     * @param array $headers
     */
    public function testRequestContentTypeIsFormUrlEncoded(array $headers)
    {
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals('application/x-www-form-urlencoded', $headers['Content-Type']);
    }

    /**
     * @depends testRequestSendsHeaders
     * @param array $headers
     */
    public function testRequestConnectionIsClose(array $headers)
    {
        $this->assertArrayHasKey('Connection', $headers);
        $this->assertEquals('close', $headers['Connection']);
    }

    /**
     * @depends testCallsRequest
     * @param array $options
     * @return array
     */
    public function testSendsFormDataInRequestBody(array $options)
    {
        parse_str($options['body'], $formData);
        $this->assertNotEmpty($formData, 'No form data in the body');
        return $formData;
    }

    /**
     * @depends testSendsFormDataInRequestBody
     * @param array $formData
     */
    public function testSendsAppKeyInFormData(array $formData)
    {
        $this->assertArrayHasKey('app_key', $formData);
        $this->assertEquals($this->appKey, $formData['app_key']);
    }

    /**
     * @depends testSendsFormDataInRequestBody
     * @param array $formData
     */
    public function testSendsEncryptedSecretKeyInFormData(array $formData)
    {
        $this->assertArrayHasKey('secret_key', $formData);
        $this->assertEquals(base64_encode($this->rsaEncrypted), $formData['secret_key']);
    }

    /**
     * @depends testSendsEncryptedSecretKeyInFormData
     */
    public function testCreatedValidEncryptedSecretKey()
    {
        $before = new \DateTime();
        $this->apiService->poll(null);
        $after = new \DateTime();
        $this->assertLastItemRsaEncryptedWasValidSecretKey($before, $after);
    }

    /**
     * @depends testSendsFormDataInRequestBody
     * @param array $formData
     */
    public function testSendsSignatureInFormData(array $formData)
    {
        $this->assertArrayHasKey('signature', $formData);
        $this->assertEquals($this->signed, $formData['signature']);
    }

    /**
     * @depends testSendsFormDataInRequestBody
     * @param array $formData
     */
    public function testSendsAuthRequestInFormData(array $formData)
    {
        $this->assertArrayHasKey('auth_request', $formData);
        $this->assertEquals("AUTH_REQUEST", $formData['auth_request']);
    }

    /**
     * @depends testCallsRequest
     * @return \LaunchKey\SDK\Domain\AuthRequest
     */
    public function testReturnsAuthResponseForPendingResponse()
    {
        $this->response = $this->pendingResponse;
        $response = $this->apiService->poll('Auth Request ID');
        $this->assertInstanceOf('LaunchKey\SDK\Domain\AuthResponse', $response);
        return $response;
    }

    /**
     * @depends testReturnsAuthResponseForPendingResponse
     * @param AuthResponse $authResponse
     */
    public function testSetsAuthRequestIdNullInAuthResponseForPendingResponse(AuthResponse $authResponse)
    {
        $this->assertNull($authResponse->getAuthRequestId());
    }

    /**
     * @depends testReturnsAuthResponseForPendingResponse
     * @param AuthResponse $authResponse
     */
    public function testSetsCompletedFalseInAuthResponseForPendingResponse(AuthResponse $authResponse)
    {
        $this->assertFalse($authResponse->isCompleted());
    }

    /**
     * @depends testReturnsAuthResponseForPendingResponse
     * @param AuthResponse $authResponse
     */
    public function testSetsAuthorizedNullInAuthResponseForPendingResponse(AuthResponse $authResponse)
    {
        $this->assertNull($authResponse->isAuthorized());
    }

    /**
     * @depends testReturnsAuthResponseForPendingResponse
     * @param AuthResponse $authResponse
     */
    public function testDoesNotSetUserHashNullInAuthResponseForPendingResponse(AuthResponse $authResponse)
    {
        $this->assertNull($authResponse->getUserHash());
    }

    /**
     * @depends testReturnsAuthResponseForPendingResponse
     * @param AuthResponse $authResponse
     */
    public function testSetsOrganizationUserIdNullInAuthResponseForPendingResponse(AuthResponse $authResponse)
    {
        $this->assertNull( $authResponse->getOrganizationUserId());
    }

    /**
     * @depends testReturnsAuthResponseForPendingResponse
     * @param AuthResponse $authResponse
     */
    public function testSetsUserPushIdNullInAuthResponseForPendingResponse(AuthResponse $authResponse)
    {
        $this->assertNull($authResponse->getUserPushId());
    }

    /**
     * @depends testCallsRequest
     */
    public function testDecryptsAuthOnOkayResponse()
    {
        $response = $this->apiService->poll(null);
        \Phake::verify($this->cryptService)->decryptRSA("Encrypted Auth");
        return $response;
    }

    /**
     * @depends testDecryptsAuthOnOkayResponse
     * @return \LaunchKey\SDK\Domain\AuthResponse
     */
    public function testReturnsAuthResponseForOkayResponse(AuthResponse $response)
    {
        $this->assertInstanceOf('\LaunchKey\SDK\Domain\AuthResponse', $response);
        return $response;
    }

    /**
     * @depends testReturnsAuthResponseForOkayResponse
     * @param AuthResponse $authResponse
     */
    public function testSetsAuthRequestIdInAuthResponseForOkResponse(AuthResponse $authResponse)
    {
        $this->assertEquals("Auth Request", $authResponse->getAuthRequestId());
    }

    /**
     * @depends testReturnsAuthResponseForOkayResponse
     * @param AuthResponse $authResponse
     */
    public function testSetsCompletedTrueInAuthResponseForOkResponse(AuthResponse $authResponse)
    {
        $this->assertTrue($authResponse->isCompleted());
    }

    /**
     * @depends testReturnsAuthResponseForOkayResponse
     * @param AuthResponse $authResponse
     */
    public function testSetsAuthorizedTrueInAuthResponseForOkResponseAndTrue(AuthResponse $authResponse)
    {
        \Phake::when($this->cryptService)->decryptRSA(\Phake::anyParameters())->thenReturn(
            '{ "device_id": "Device ID", "response": "true", "auth_request": "Auth Request", "app_pins": "APP,PINS"}'
        );
        $this->assertTrue($authResponse->isAuthorized());
    }

    /**
     * @depends testReturnsAuthResponseForOkayResponse
     * @throws \Exception
     * @throws \LaunchKey\SDK\Service\Exception\InvalidRequestError
     * @internal param AuthResponse $authResponse
     */
    public function testSetsAuthorizedFalseInAuthResponseForOkResponseAndFalse()
    {
        \Phake::when($this->cryptService)->decryptRSA(\Phake::anyParameters())->thenReturn(
            '{ "device_id": "Device ID", "response": "false", "auth_request": "Auth Request", "app_pins": "APP,PINS"}'
        );
        $response = $this->apiService->poll(null);
        $this->assertFalse($response->isAuthorized());
    }

    /**
     * @depends testReturnsAuthResponseForOkayResponse
     * @param AuthResponse $authResponse
     */
    public function testSetsUserHashInAuthResponseForOkResponse(AuthResponse $authResponse)
    {
        $this->assertEquals("User Hash", $authResponse->getUserHash());
    }

    /**
     * @depends testReturnsAuthResponseForOkayResponse
     * @param AuthResponse $authResponse
     */
    public function testSetsOrganizationUserIdInAuthResponseForOkResponse(AuthResponse $authResponse)
    {
        $this->assertEquals("Org User", $authResponse->getOrganizationUserId());
    }

    /**
     * @depends testReturnsAuthResponseForOkayResponse
     * @param AuthResponse $authResponse
     */
    public function testSetsUserPushIdInAuthResponseForOkResponse(AuthResponse $authResponse)
    {
        $this->assertEquals("User Push Id", $authResponse->getUserPushId());
    }

    /**
     * @depends testReturnsAuthResponseForOkayResponse
     * @param AuthResponse $authResponse
     */
    public function testSetsDeviceIdInAuthResponseForOkResponse(AuthResponse $authResponse)
    {
        $this->assertEquals("Device ID", $authResponse->getDeviceId());
    }

    /**
     * @depends testCallsRequest
     */
    public function testThrowsInvalidResponseErrorWhenBodyIsNotParseable()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidResponseError');
        $this->response['body'] = 'Invalid JSON';
        $this->apiService->poll(null);
    }

    /**
     * @depends testCallsRequest
     */
    public function testDebugLogsWhenLoggerIsPresent()
    {
        $this->loggingApiService->poll(null);
        Phake::verify($this->logger, Phake::atLeast(1))->debug(Phake::anyParameters());
    }

    /**
     * @depends testCallsRequest
     */
    public function testThrowsCommunicationErrorOnServerError()
    {
        $this->setExpectedException(
            '\LaunchKey\SDK\Service\Exception\CommunicationError',
            'error => messages'
        );
        phake::when($this->client)->request(Phake::anyParameters())->thenReturn($this->wpError);
        $this->apiService->poll(null);
    }

    /**
     * @depends testCallsRequest
     */
    public function testThrowsInvalidRequestOn400()
    {
        $this->setExpectedException(
            '\LaunchKey\SDK\Service\Exception\InvalidRequestError',
            '{"username":"Invalid character used. Do not use &gt; &lt; ) ( @ : ; &amp;"}',
            40421
        );
        $this->response['response']['code'] = 400;
        $this->response['body'] = '{"successful": false, "status_code": 400, "message": {"username": "Invalid character used. Do not use &gt; &lt; ) ( @ : ; &amp;"}, "message_code": 40421, "response": ""}';
        $this->apiService->poll(null);
    }
}
