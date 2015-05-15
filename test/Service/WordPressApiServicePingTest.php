<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use LaunchKey\SDK\Domain\PingResponse;
use Phake;

class WordPressApiServicePingTest extends WordPressApiServiceTestAbstract
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
                'Content-Length' => '553',
                'Connection' => 'keep-alive',
                'Date' => 'Thu, 12 Mar 2015 16:55:12 GMT'

            ),
            'body' => '{"date_stamp": "2013-04-20 21:40:02", "launchkey_time": "2015-03-12 16:55:12", "key": "-----BEGIN PUBLIC KEY-----\n\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA8zQos4iDSjmUVrFUAg5G\nuhU6GehNKb8MCXFadRWiyLGjtbGZAk8fusQU0Uj9E3o0mne0SYESACkhyK+3M1Er\nbHlwYJHN0PZHtpaPWqsRmNzui8PvPmhm9QduF4KBFsWu1sBw0ibBYsLrua67F/wK\nPaagZRnUgrbRUhQuYt+53kQNH9nLkwG2aMVPxhxcLJYPzQCat6VjhHOX0bgiNt1i\nHRHU2phxBcquOW2HpGSWcpzlYgFEhPPQFAxoDUBYZI3lfRj49gBhGQi32qQ1YiWp\naFxOB8GA0Ny5SfI67u6w9Nz9Z9cBhcZBfJKdq5uRWjZWslHjBN3emTAKBpAUPNET\nnwIDAQAB\n\n-----END PUBLIC KEY-----\n"}',
            'response' => array('code' => 200, 'message' => 'OK'),
            'cookies' => array(),
            'filename' => null
        );
    }

    public function testCallsRequest()
    {
        $this->apiService->ping();
        Phake::verify($this->client)->request($this->anything(), Phake::capture($options));
        return $options;
    }

    /**
     * @depends testCallsRequest
     */
    public function testRequestUsesCorrectURL()
    {
        $this->apiService->ping();
        Phake::verify($this->client)->request('https://api.base.url/v1/ping', $this->anything());
    }

    /**
     * @depends testCallsRequest
     * @param array $options
     */
    public function testRequestMethodIsGET(array $options)
    {
        $this->assertEquals('GET', $options['method']);
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

    public function testReturnsPingResponse()
    {
        $response = $this->apiService->ping();
        $this->assertNotNull($response);
        $this->assertInstanceOf('LaunchKey\SDK\Domain\PingResponse', $response);
        return $response;
    }

    /**
     * @depends testReturnsPingResponse
     * @param PingResponse $response
     */
    public function testPutsLaunchKeyTimeFromResponseInThePingResponseWithUTC(PingResponse $response)
    {
        $expected = new \DateTime("2015-03-12 16:55:12", new \DateTimeZone("UTC"));
        $this->assertEquals($expected, $response->getLaunchKeyTime());
    }

    /**
     * @depends testReturnsPingResponse
     * @param PingResponse $response
     */
    public function testPutsKeyTimeStampFromResponseInThePingResponseWithUTC(PingResponse $response)
    {
        $expected = new \DateTime("2013-04-20 21:40:02", new \DateTimeZone("UTC"));
        $this->assertEquals($expected, $response->getKeyTimeStamp());
    }

    /**
     * @depends testReturnsPingResponse
     * @param PingResponse $response
     */
    public function testPutsKeyFromResponseInThePingResponse(PingResponse $response)
    {
        $expected = "-----BEGIN PUBLIC KEY-----\n\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA8zQos4iDSjmUVrFUAg5G\nuhU6GehNKb8MCXFadRWiyLGjtbGZAk8fusQU0Uj9E3o0mne0SYESACkhyK+3M1Er\nbHlwYJHN0PZHtpaPWqsRmNzui8PvPmhm9QduF4KBFsWu1sBw0ibBYsLrua67F/wK\nPaagZRnUgrbRUhQuYt+53kQNH9nLkwG2aMVPxhxcLJYPzQCat6VjhHOX0bgiNt1i\nHRHU2phxBcquOW2HpGSWcpzlYgFEhPPQFAxoDUBYZI3lfRj49gBhGQi32qQ1YiWp\naFxOB8GA0Ny5SfI67u6w9Nz9Z9cBhcZBfJKdq5uRWjZWslHjBN3emTAKBpAUPNET\nnwIDAQAB\n\n-----END PUBLIC KEY-----\n";
        $this->assertEquals($expected, $response->getPublicKey());
    }

    /**
     * @depends testCallsRequest
     */
    public function testThrowsInvalidResponseErrorWhenBodyIsNotParseable()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidResponseError');
        $this->response['body'] = 'Invalid JSON';
        $this->apiService->ping();
    }

    /**
     * @depends testCallsRequest
     */
    public function testDebugLogsWhenLoggerIsPresent()
    {
        $this->loggingApiService->ping();
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
        $this->apiService->ping();
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
        $this->apiService->ping();
    }
}
