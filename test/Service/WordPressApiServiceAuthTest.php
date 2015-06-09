<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use LaunchKey\SDK\Domain\AuthRequest;
use Phake;

class WordPressApiServiceAuthTest extends WordPressApiServiceTestAbstract
{
    protected function setUp()
    {
        parent::setUp();
        $this->response = array(
            'headers' => array(
                'Server' => 'nginx',
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Length' => '35',
                'Connection' => 'close',
                'Date' => 'Thu, 12 Mar 2015 16:55:12 GMT'

            ),
            'body' => '{"auth_request": "Auth Request ID"}',
            'response' => array('code' => 200, 'message' => 'OK'),
            'cookies' => array(),
            'filename' => null
        );
    }

    public function testCallsRequest()
    {
        $this->apiService->auth("user name", true);
        Phake::verify($this->client)->request($this->anything(), Phake::capture($options));
        return $options;
    }

    /**
     * @depends testCallsRequest
     */
    public function testRequestUsesCorrectURL()
    {
        $this->apiService->auth(null, null);
        Phake::verify($this->client)->request('https://api.base.url/v1/auths', $this->anything());
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
        $this->apiService->auth(null, null);
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
    public function testSendsUsernameInFormData(array $formData)
    {
        $this->assertArrayHasKey('username', $formData);
        $this->assertEquals("user name", $formData['username']);
    }

    /**
     * @depends testSendsFormDataInRequestBody
     * @param array $formData
     */
    public function testSendsNumericOneForUserPushIdInFormData(array $formData)
    {
        $this->assertArrayHasKey('user_push_id', $formData);
        $this->assertEquals("1", $formData['user_push_id']);
    }

    /**
     * @depends testSendsFormDataInRequestBody
     */
    public function testSendsNumericOneForSessionWhenTrue()
    {
        $this->apiService->auth(null, true);
        Phake::verify($this->client)->request($this->anything(), Phake::capture($options));
        parse_str($options['body'], $formData);
        $this->assertArrayHasKey('session', $formData);
        $this->assertEquals("1", $formData['session']);
    }

    /**
     * @depends testSendsFormDataInRequestBody
     */
    public function testSendsNumericZeroForSessionWhenFalse()
    {
        $this->apiService->auth(null, false);
        Phake::verify($this->client)->request($this->anything(), Phake::capture($options));
        parse_str($options['body'], $formData);
        $this->assertArrayHasKey('session', $formData);
        $this->assertEquals("0", $formData['session']);
    }

    /**
     * @depends testCallsRequest
     * @return \LaunchKey\SDK\Domain\AuthRequest
     */
    public function testReturnsAuthRequestObject()
    {
        $response = $this->apiService->auth("user name", true);
        $this->assertInstanceOf('LaunchKey\SDK\Domain\AuthRequest', $response);
        return $response;
    }

    /**
     * @depends testReturnsAuthRequestObject
     */
    public function testReturnsProperAuthRequestIdentifierInAuthRequestReturned(AuthRequest $authRequest)
    {
        $this->assertEquals("Auth Request ID", $authRequest->getAuthRequestId());
    }

    /**
     * @depends testReturnsAuthRequestObject
     */
    public function testReturnsProperUserNameInAuthRequestReturned(AuthRequest $authRequest)
    {
        $this->assertEquals("user name", $authRequest->getUsername());
    }

    /**
     * @depends testReturnsAuthRequestObject
     */
    public function testReturnsProperSessionValueInAuthRequestReturned(AuthRequest $authRequest)
    {
        $this->assertEquals(true, $authRequest->isUserSession());
    }

    /**
     * @depends testCallsRequest
     */
    public function testThrowsInvalidResponseErrorWhenBodyIsNotParseable()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidResponseError');
        $this->response['body'] = 'Invalid JSON';
        $this->apiService->auth(null, null);
    }

    /**
     * @depends testCallsRequest
     */
    public function testDebugLogsWhenLoggerIsPresent()
    {
        $this->loggingApiService->auth(null, null);
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
        $this->apiService->auth(null, null);
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
        $this->apiService->auth(null, null);
    }
}
