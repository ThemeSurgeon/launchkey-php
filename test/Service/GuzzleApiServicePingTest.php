<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;

use Phake;

class GuzzleApiServicePingTest extends GuzzleApiServiceTestAbstract
{
    public function testPingSendsGet()
    {
        $this->apiService->ping();
        $this->assertGuzzleRequestMethodEquals('GET');
    }

    public function testPingUsesPingPath()
    {
        $this->apiService->ping();
        $this->assertGuzzleRequestPathEquals('/ping');
    }

    public function testPingPutsLaunchKeyTimeFromResponseInThePingResponseWithUTC()
    {
        $response = $this->apiService->ping();
        $expected = new \DateTime("2015-03-12 16:55:12", new \DateTimeZone("UTC"));
        $this->assertEquals($expected, $response->getLaunchKeyTime());
    }

    public function testPingPutsKeyTimeStampFromResponseInThePingResponseWithUTC()
    {
        $response = $this->apiService->ping();
        $expected = new \DateTime("2013-04-20 21:40:02", new \DateTimeZone("UTC"));
        $this->assertEquals($expected, $response->getKeyTimeStamp());
    }

    public function testPingPutsKeyFromResponseInThePingResponse()
    {
        $expected = "-----BEGIN PUBLIC KEY-----\n\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA8zQos4iDSjmUVrFUAg5G\nuhU6GehNKb8MCXFadRWiyLGjtbGZAk8fusQU0Uj9E3o0mne0SYESACkhyK+3M1Er\nbHlwYJHN0PZHtpaPWqsRmNzui8PvPmhm9QduF4KBFsWu1sBw0ibBYsLrua67F/wK\nPaagZRnUgrbRUhQuYt+53kQNH9nLkwG2aMVPxhxcLJYPzQCat6VjhHOX0bgiNt1i\nHRHU2phxBcquOW2HpGSWcpzlYgFEhPPQFAxoDUBYZI3lfRj49gBhGQi32qQ1YiWp\naFxOB8GA0Ny5SfI67u6w9Nz9Z9cBhcZBfJKdq5uRWjZWslHjBN3emTAKBpAUPNET\nnwIDAQAB\n\n-----END PUBLIC KEY-----\n";
        $response = $this->apiService->ping();
        $this->assertEquals($expected, $response->getPublicKey());
    }

    public function testPingThrowsInvalidResponseErrorWhenBodyIsNotParseable()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidResponseError');
        $this->setFixtureResponse("api_responses/invalid.txt");
        $this->apiService->ping();
    }

    public function testPingDebugLogsWhenLoggerIsPresent()
    {
        $this->loggingApiService->ping();
        Phake::verify($this->logger, Phake::atLeast(1))->debug(Phake::anyParameters());
    }

    public function testPingThrowsCommunicationErrorOnServerError()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        $this->setFixtureResponse("api_responses/server_error.txt");
        $this->apiService->ping();
    }

    public function testPingThrowsInvalidRequestOn400()
    {
        $this->setExpectedException(
            '\LaunchKey\SDK\Service\Exception\InvalidRequestError',
            '{"username":"Invalid character used. Do not use &gt; &lt; ) ( @ : ; &amp;"}',
            40421
        );
        $this->setFixtureResponse("api_responses/request_error.txt");
        $this->apiService->ping();
    }

    protected function setUp()
    {
        parent::setUp();
        $this->setFixtureResponse("api_responses/ping_ok.txt");
    }
}
