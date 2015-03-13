<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;

use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Mock\MockPlugin;
use LaunchKey\SDK\Domain\AuthRequest;
use LaunchKey\SDK\Service\CryptService;
use LaunchKey\SDK\Service\GuzzleApiService;
use LaunchKey\SDK\Test\FixtureTestAbstract;
use Phake;
use Psr\Log\LoggerInterface;

class GuzzleApiServiceTest extends FixtureTestAbstract
{
    /**
     * @var ClientInterface
     */
    private $guzzleClient;

    /**
     * @var MockPlugin
     */
    private $guzzleMockPlugin;

    /**
     * @Mock
     * @var CryptService
     */
    private $cryptService;

    /**
     * @Mock
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GuzzleApiService
     */
    private $apiService;

    /**
     * @var GuzzleApiService
     */
    private $loggingApiService;

    public function testPingSendsGet()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/ping/ok.txt"));
        $this->apiService->ping();
        $this->assertEquals('GET', $this->getGuzzleRequest()->getMethod());
    }

    public function testPingUsesPingPath()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/ping/ok.txt"));
        $this->apiService->ping();
        $this->assertEquals('/ping', $this->getGuzzleRequest()->getPath());
    }

    public function testPingPutsLaunchKeyTimeFromResponseInThePingResponseWithUTC()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/ping/ok.txt"));
        $response = $this->apiService->ping();
        $expected = new \DateTime("2015-03-12 16:55:12", new \DateTimeZone("UTC"));
        $this->assertEquals($expected, $response->getLaunchKeyTime());
    }

    public function testPingPutsKeyTimeStampFromResponseInThePingResponseWithUTC()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/ping/ok.txt"));
        $response = $this->apiService->ping();
        $expected = new \DateTime("2013-04-20 21:40:02", new \DateTimeZone("UTC"));
        $this->assertEquals($expected, $response->getKeyTimeStamp());
    }

    public function testPingPutsKeyFromResponseInThePingResponse()
    {
        $expected = "-----BEGIN PUBLIC KEY-----\n\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA8zQos4iDSjmUVrFUAg5G\nuhU6GehNKb8MCXFadRWiyLGjtbGZAk8fusQU0Uj9E3o0mne0SYESACkhyK+3M1Er\nbHlwYJHN0PZHtpaPWqsRmNzui8PvPmhm9QduF4KBFsWu1sBw0ibBYsLrua67F/wK\nPaagZRnUgrbRUhQuYt+53kQNH9nLkwG2aMVPxhxcLJYPzQCat6VjhHOX0bgiNt1i\nHRHU2phxBcquOW2HpGSWcpzlYgFEhPPQFAxoDUBYZI3lfRj49gBhGQi32qQ1YiWp\naFxOB8GA0Ny5SfI67u6w9Nz9Z9cBhcZBfJKdq5uRWjZWslHjBN3emTAKBpAUPNET\nnwIDAQAB\n\n-----END PUBLIC KEY-----\n";
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/ping/ok.txt"));
        $response = $this->apiService->ping();
        $this->assertEquals($expected, $response->getPublicKey());
    }

    public function testPingThrowsCommunicationErrorOnNon200Status()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/server_error.txt"));
        $this->apiService->ping();
    }

    public function testPingThrowsInvalidResponseErrorWhenBodyIsNotParseable()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidResponseError');
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/invalid.txt"));
        $this->apiService->ping();
    }

    public function testPingDebugLogsWhenLoggerIsPresent()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/ping/ok.txt"));
        $this->loggingApiService->ping();
        Phake::verify($this->logger, Phake::atLeast(1))->debug(Phake::anyParameters());
    }

    public function testAuthSendsPost()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/auth/ok.txt"));
        $this->apiService->auth(null, null, null, null, null);
        $this->assertEquals('POST', $this->getGuzzleRequest()->getMethod());
    }

    public function testAuthSendsContentTypeFormUrlEncoded()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/auth/ok.txt"));
        $this->apiService->auth(null, null, null, null, null);
        $contentTypes = $this->getGuzzleRequest()->getHeader('content-type')->toArray();
        $this->assertStringStartsWith('application/x-www-form-urlencoded', $contentTypes[0]);
    }

    public function testAuthSendsAppKeyInRequestFormData()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/auth/ok.txt"));
        $this->apiService->auth(null, null, "APP-KEY", null, null);
        $this->assertEquals('APP-KEY', $this->getGuzzleRequest()->getPostField('app_key'));
    }

    public function testAuthSendsEncryptedSecretKeyInFormData()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/auth/ok.txt"));
        $this->apiService->auth(null, null, null, null, null);
        $this->assertEquals(base64_encode("RSA Encrypted"), $this->getGuzzleRequest()->getPostField('secret_key'));
    }

    public function testAuthUsesSecretKeyAndCurrentTimeInnLaunchKeyTimeFormatForEncryptedSecretKey()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/auth/ok.txt"));
        $tz = new \DateTimeZone("UTC");
        $before = new \DateTime("now", $tz);
        $this->apiService->auth(null, null, null,"secret-key", "public-key");
        $after = new \DateTime("now", $tz);
        $json = $publicKey = null;
        Phake::verify($this->cryptService)->encryptRSA(Phake::capture($json), Phake::capture($publicKey), false);
        $this->assertEquals("public-key", $publicKey, "Enexpected value for public key in encryptRSA");
        $this->assertJson($json, "Data passed to encryptRSA for secret_key was not valid JSON");
        $data = json_decode($json, true);
        $this->assertArrayHasKey("secret", $data, "Encrypted secret has no secret attribute");
        $this->assertEquals("secret-key", $data["secret"], "Unexpected value for secret_key secret");
        $this->assertArrayHasKey("stamped", $data, "Encrypted secret has no stamped attribute");
        $this->assertStringMatchesFormat(
            '%d-%d-%d %d:%d:%d', $data['stamped'], "secret_key stamped attribute has incorrect format"
        );
        $actual = \DateTime::createFromFormat("Y-m-d H:i:s", $data['stamped'], $tz);
        $this->assertGreaterThanOrEqual($before, $actual, "secret_key stamped is before the call occurred");
        $this->assertLessThanOrEqual($after, $actual, "secret_key stamped is before the call occurred");
    }

    public function testAuthSendsSignatureInFormData()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/auth/ok.txt"));
        $this->apiService->auth(null, null, null, null, null);
        $this->assertEquals("Signed", $this->getGuzzleRequest()->getPostField('signature'));
    }

    public function testAuthSignsTheSecretKeyForTheSignature()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/auth/ok.txt"));
        $this->apiService->auth(null, null, null, null, null);
        Phake::verify($this->cryptService)->sign("RSA Encrypted");
    }

    public function testAuthSendsUsernameInTheFormData()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/auth/ok.txt"));
        $this->apiService->auth("user name", null, null, null, null);
        $this->assertEquals("user name", $this->getGuzzleRequest()->getPostField('username'));
    }

    public function testAuthSendsNumericOneForSessionWhenTrue()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/auth/ok.txt"));
        $this->apiService->auth(null, true, null, null, null);
        $this->assertSame(1, $this->getGuzzleRequest()->getPostField('session'));
    }

    public function testAuthSendsNumericZeroForSessionWhenFalse()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/auth/ok.txt"));
        $this->apiService->auth(null, false, null, null, null);
        $this->assertSame(0, $this->getGuzzleRequest()->getPostField('session'));
    }

    public function testAuthSendsNumericOneForUserPushId()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/auth/ok.txt"));
        $this->apiService->auth(null, true, null, null, null);
        $this->assertSame(1, $this->getGuzzleRequest()->getPostField('user_push_id'));
    }

    public function testAuthReturnsAuthRequestObject()
    {
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/auth/ok.txt"));
        $response = $this->apiService->auth(null, null, null, null, null);
        $this->assertInstanceOf('LaunchKey\SDK\Domain\AuthRequest', $response);
        return $response;
    }

    /**
     * @depends testAuthReturnsAuthRequestObject
     */
    public function testAuthReturnsProperAuthRequestIdentifierInAuthRequestReturned(AuthRequest $authRequest)
    {
        $this->assertEquals("Auth Request ID", $authRequest->getAuthRequestId());
    }

    public function testAuthThrowsInvalidResponseErrorWhenBodyIsNotParseable()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidResponseError');
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/invalid.txt"));
        $this->apiService->ping();
    }

    public function testPingThrowsCommunicationErrorOnServerError()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/server_error.txt"));
        $this->apiService->ping();
    }

    public function testPingThrowsInvalidRequestOn400()
    {
        $this->setExpectedException(
            '\LaunchKey\SDK\Service\Exception\InvalidRequestError',
            '{"username":"Invalid character used. Do not use &gt; &lt; ) ( @ : ; &amp;"}',
            40421
        );
        $this->guzzleMockPlugin->addResponse($this->getResponse("api_responses/request_error.txt"));
        $this->apiService->ping();
    }

    protected function setUp()
    {
        Phake::initAnnotations($this);
        Phake::when($this->cryptService)->encryptRSA(Phake::anyParameters())->thenReturn("RSA Encrypted");
        Phake::when($this->cryptService)->sign(Phake::anyParameters())->thenReturn("Signed");
        $this->guzzleClient = new Client();
        $this->guzzleMockPlugin = new MockPlugin();
        $this->guzzleClient->addSubscriber($this->guzzleMockPlugin);
        $this->apiService = new GuzzleApiService($this->guzzleClient, $this->cryptService);
        $this->loggingApiService = new GuzzleApiService($this->guzzleClient, $this->cryptService, $this->logger);
    }

    protected function tearDown()
    {
        $this->guzzleClient = null;
        $this->guzzleMockPlugin = null;
        $this->cryptService = null;
        $this->logger = null;
        $this->apiService = null;
        $this->loggingApiService = null;
    }

    private function getResponse($fixture)
    {
        return Response::fromMessage($this->getFixture($fixture));
    }

    /**
     * @return EntityEnclosingRequestInterface
     */
    private function getGuzzleRequest()
    {
        $requests = $this->guzzleMockPlugin->getReceivedRequests();
        return $requests[0];
    }
}
