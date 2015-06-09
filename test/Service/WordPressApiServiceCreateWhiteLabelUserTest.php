<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use LaunchKey\SDK\Domain\AuthRequest;
use LaunchKey\SDK\Domain\WhiteLabelUser;
use Phake;

class WordPressApiServiceCreateWhiteLabelUserTest extends WordPressApiServiceTestAbstract
{
    private $qrCodeUrl = 'QR Code URL';

    private $code = 'Code';

    protected function setUp()
    {
        parent::setUp();
        $this->response = array(
            'headers' => array(
                'Server' => 'nginx',
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Length' => '188',
                'Connection' => 'close',
                'Date' => 'Thu, 12 Mar 2015 16:55:12 GMT'

            ),
            'body' => '{"successful": true, "status_code": 201, "message": "", "message_code": 10220, "response": {"cipher": "Base64 Encrypted RSA Encrypted Cipher", "data": "Base64 Encoded AES Encrypted Data"}}',
            'response' => array('code' => 201, 'message' => 'OK'),
            'cookies' => array(),
            'filename' => null
        );
        \Phake::when($this->cryptService)
              ->decryptAES(\Phake::anyParameters())
              ->thenReturn('{"qrcode": "' . $this->qrCodeUrl . '", "code": "' . $this->code . '"}');

    }

    public function testCallsRequest()
    {
        $this->apiService->createWhiteLabelUser('IDENTIFIER');
        Phake::verify($this->client)->request($this->anything(), Phake::capture($options));
        return $options;
    }

    /**
     * @depends testCallsRequest
     */
    public function testRequestUsesCorrectURL()
    {
        $this->apiService->createWhiteLabelUser(null);
        Phake::verify($this->client)->request(
            new \PHPUnit_Framework_Constraint_StringStartsWith('https://api.base.url/v1/users'),
            $this->anything()
        );
    }

    /**
     * @depends testCallsRequest
     */
    public function testSendsSignatureAsQueryString()
    {
        \Phake::when($this->cryptService)->sign(\Phake::anyParameters())->thenReturn("Expected Signature");
        $this->apiService->createWhiteLabelUser(null);
        Phake::verify($this->client)->request(
            new \PHPUnit_Framework_Constraint_StringEndsWith('?signature=Expected+Signature'),
            $this->anything()
        );
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
        $this->assertEquals('application/json', $headers['Content-Type']);
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
     */
    public function testSignsEntireBody(array $options)
    {
        $this->apiService->createWhiteLabelUser(null);
        Phake::verify($this->client)->request($this->anything(), Phake::capture($options));
        \Phake::verify($this->cryptService)->sign($options['body']);
    }

    /**
     * @depends testCallsRequest
     * @param array $options
     * @return array
     */
    public function testSendsJsonDataInRequestBody(array $options)
    {
        $data = json_decode($options['body'], true);
        $this->assertNotEmpty($data, 'No JSON data in the body');
        return $data;
    }

    /**
     * @depends testSendsJsonDataInRequestBody
     * @param array $data
     */
    public function testSendsAppKeyInData(array $data)
    {
        $this->assertArrayHasKey('app_key', $data);
        $this->assertEquals($this->appKey, $data['app_key']);
    }

    /**
     * @depends testSendsJsonDataInRequestBody
     * @param array $data
     */
    public function testSendsEncryptedSecretKeyInData(array $data)
    {
        $this->assertArrayHasKey('secret_key', $data);
        $this->assertEquals(base64_encode($this->rsaEncrypted), $data['secret_key']);
    }

    /**
     * @depends testSendsJsonDataInRequestBody
     */
    public function testCreatedValidEncryptedSecretKey()
    {
        $before = new \DateTime();
        $this->apiService->createWhiteLabelUser(null);
        $after = new \DateTime();
        $this->assertLastItemRsaEncryptedWasValidSecretKey($before, $after);
    }

    /**
     * @depends testSendsJsonDataInRequestBody
     * @param array $data
     */
    public function testSendsIdentifierInData(array $data)
    {
        $this->assertArrayHasKey('identifier', $data);
        $this->assertEquals("IDENTIFIER", $data['identifier']);
    }

    public function testDecryptsBodyDataCorrectly()
    {
        \Phake::when($this->cryptService)
              ->decryptRSA(\Phake::anyParameters())
              ->thenReturn("KeyKeyKeyKeyKeyKeyKeyKeyKeyKey32IvIvIvIvIvIvIvIv");
        $this->apiService->createWhiteLabelUser(null);
        \Phake::verify($this->cryptService)->decryptRSA("Base64 Encrypted RSA Encrypted Cipher");
        \Phake::verify($this->cryptService)->decryptAES(
            "Base64 Encoded AES Encrypted Data",
            "KeyKeyKeyKeyKeyKeyKeyKeyKeyKey32",
            "IvIvIvIvIvIvIvIv"
        );
    }

    /**
     * @depends testCallsRequest
     * @return \LaunchKey\SDK\Domain\WhiteLabelUser
     */
    public function testReturnsWhiteLabelUserObject()
    {
        $response = $this->apiService->createWhiteLabelUser(null);
        $this->assertInstanceOf('LaunchKey\SDK\Domain\WhiteLabelUser', $response);
        return $response;
    }

    /**
     * @depends testReturnsWhiteLabelUserObject
     */
    public function testSetsQrCodeCorrectlyOnReturnedWhiteLabelUser(WhiteLabelUser $response)
    {
        $this->assertEquals($this->qrCodeUrl, $response->getQrCodeUrl());
    }

    /**
     * @depends testReturnsWhiteLabelUserObject
     */
    public function testSetsCodeCorrectlyOnReturnedWhiteLabelUser(WhiteLabelUser $response)
    {
        $this->assertEquals($this->code, $response->getCode());
    }

    /**
     * @depends testCallsRequest
     */
    public function testThrowsInvalidResponseErrorWhenBodyIsNotParseable()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidResponseError');
        $this->response['body'] = 'Invalid JSON';
        $this->apiService->createWhiteLabelUser(null);
    }

    /**
     * @depends testCallsRequest
     */
    public function testDebugLogsWhenLoggerIsPresent()
    {
        $this->loggingApiService->createWhiteLabelUser(null);
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
        $this->apiService->createWhiteLabelUser(null);
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
        $this->apiService->createWhiteLabelUser(null);
    }

    /**
     * @depends testCallsRequest
     */
    public function testThrowsInvalidResponseWhenEncryptedDataIsNotJSON()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidResponseError');
        Phake::when($this->cryptService)->decryptAES(Phake::anyParameters())->thenReturn('Invalid JSON');
        $this->apiService->createWhiteLabelUser(null);
    }
}
