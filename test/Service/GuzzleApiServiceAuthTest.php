<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;

use LaunchKey\SDK\Domain\AuthRequest;
use Phake;

class GuzzleApiServiceAuthTest extends GuzzleApiServiceTestAbstract
{
    public function testAuthSendsPost()
    {
        $this->apiService->auth(null, null);
        $this->assertGuzzleRequestMethodEquals('POST');
    }

    public function testAuthUSesCorrectPath()
    {
        $this->apiService->auth(null, null);
        $this->assertGuzzleRequestPathEquals('/v1/auths');
    }

    public function testAuthSendsContentTypeFormUrlEncoded()
    {
        $this->apiService->auth(null, null);
        $this->assertGuzzleRequestHeaderStartsWith("content-type", 'application/x-www-form-urlencoded');
    }

    public function testAuthSendsAppKeyInRequestFormData()
    {
        $this->apiService->auth(null, null);
        $this->assertGuzzleRequestFormFieldValueEquals("app_key", $this->appKey);
    }

    public function testAuthSendsEncryptedSecretKeyInFormData()
    {
        $this->apiService->auth(null, null);
        $this->assertGuzzleRequestFormFieldValueEquals('secret_key', base64_encode("RSA Encrypted"));
    }

    public function testAuthUsesSecretKeyAndCurrentTimeInLaunchKeyTimeFormatForEncryptedSecretKey()
    {
        $before = new \DateTime();
        $this->apiService->auth(null, null);
        $after = new \DateTime();
        $this->assertLastItemRsaEncryptedWasValidSecretKey($before, $after);
    }

    public function testAuthSendsSignatureInFormData()
    {
        $this->apiService->auth(null, null);
        $this->assertGuzzleRequestFormFieldValueEquals('signature', "Signed");
    }

    public function testAuthSignsTheSecretKeyForTheSignature()
    {
        $this->apiService->auth(null, null);
        Phake::verify($this->cryptService)->sign("RSA Encrypted");
    }

    public function testAuthSendsUsernameInTheFormData()
    {
        $this->apiService->auth("user name", null);
        $this->assertGuzzleRequestFormFieldValueEquals("username", "user name");
    }

    public function testAuthSendsNumericOneForSessionWhenTrue()
    {
        $this->apiService->auth(null, true);
        $this->assertGuzzleRequestFormFieldValueEquals("session", 1);
    }

    public function testAuthSendsNumericZeroForSessionWhenFalse()
    {
        $this->apiService->auth(null, false);
        $this->assertGuzzleRequestFormFieldValueEquals("session", 0);
    }

    public function testAuthSendsNumericOneForUserPushId()
    {
        $this->apiService->auth(null, true);
        $this->assertGuzzleRequestFormFieldValueEquals("user_push_id", 1);
    }

    public function testAuthReturnsAuthRequestObject()
    {
        $response = $this->apiService->auth(null, null);
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
        $this->setFixtureResponse("api_responses/invalid.txt");
        $this->apiService->auth(null, null);
    }


    public function testAuthThrowsCommunicationErrorOnServerError()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        $this->setFixtureResponse("api_responses/server_error.txt");
        $this->apiService->auth(null, null);
    }

    public function testAuthThrowsInvalidRequestOn400()
    {
        $this->setExpectedException(
            '\LaunchKey\SDK\Service\Exception\InvalidRequestError',
            '{"username":"Invalid character used. Do not use &gt; &lt; ) ( @ : ; &amp;"}',
            40421
        );
        $this->setFixtureResponse("api_responses/request_error.txt");
        $this->apiService->auth(null, null);
    }

    protected function setUp()
    {
        parent::setUp();
        $this->setFixtureResponse("api_responses/auth_ok.txt");
        Phake::when($this->cryptService)->encryptRSA(Phake::anyParameters())->thenReturn("RSA Encrypted");
        Phake::when($this->cryptService)->sign(Phake::anyParameters())->thenReturn("Signed");
    }
}
