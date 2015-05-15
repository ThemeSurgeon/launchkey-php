<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use LaunchKey\SDK\Domain\PingResponse;
use Phake;

class WordPressApiServiceLogTest extends WordPressApiServiceTestAbstract
{
    /**
     * @var array
     */
    public $response;

    protected function setUp()
    {
        parent::setUp();
        $this->response = array(
            'headers' => array(
                'Server' => 'nginx',
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Length' => '36',
                'Connection' => 'close',
                'Date' => 'Thu, 12 Mar 2015 16:55:12 GMT'

            ),
            'body' => '{"message" : "Successfully updated"}',
            'response' => array('code' => 200, 'message' => 'OK'),
            'cookies' => array(),
            'filename' => null
        );
    }

    public function testCallsRequest()
    {
        $this->apiService->log("Auth Request", "Action Verb", true);
        Phake::verify($this->client)->request($this->anything(), Phake::capture($options));
        return $options;
    }

    /**
     * @depends testCallsRequest
     */
    public function testRequestUsesCorrectURL()
    {
        $this->apiService->log(null, null, null);
        Phake::verify($this->client)->request('https://api.base.url/v1/logs', $this->anything());
    }

    /**
     * @depends testCallsRequest
     * @param array $options
     */
    public function testRequestMethodIsPUT(array $options)
    {
        $this->assertEquals('PUT', $options['method']);
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
        $this->apiService->log(null, null, null);
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
    public function testSendsActionInFormData(array $formData)
    {
        $this->assertArrayHasKey('action', $formData);
        $this->assertEquals("Action Verb", $formData['action']);
    }

    /**
     * @depends testSendsFormDataInRequestBody
     * @param array $formData
     */
    public function testSendsAuthRequestInFormData(array $formData)
    {
        $this->assertArrayHasKey('auth_request', $formData);
        $this->assertEquals("Auth Request", $formData['auth_request']);
    }

    /**
     * @depends testSendsFormDataInRequestBody
     */
    public function testSendsStatusAsStringTrueInFormDataWhenTrue()
    {
        $this->apiService->log(null, null, true);
        Phake::verify($this->client)->request($this->anything(), Phake::capture($options));
        parse_str($options['body'], $formData);
        $this->assertArrayHasKey('status', $formData);
        $this->assertEquals("True", $formData['status']);
    }

    /**
     * @depends testSendsFormDataInRequestBody
     */
    public function testLogStatusAsStringFalseInFormDataWhenFalse()
    {
        $this->apiService->log(null, null, false);
        Phake::verify($this->client)->request($this->anything(), Phake::capture($options));
        parse_str($options['body'], $formData);
        $this->assertArrayHasKey('status', $formData);
        $this->assertEquals("False", $formData['status']);
    }

    /**
     * @depends testCallsRequest
     */
    public function testThrowsInvalidResponseErrorWhenBodyIsNotParseable()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidResponseError');
        $this->response['body'] = 'Invalid JSON';
        $this->apiService->log(null, null, null);
    }

    /**
     * @depends testCallsRequest
     */
    public function testDebugLogsWhenLoggerIsPresent()
    {
        $this->loggingApiService->log(null, null, null);
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
        $this->apiService->log(null, null, null);
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
        $this->apiService->log(null, null, null);
    }
}
