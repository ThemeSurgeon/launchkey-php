<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;

class GuzzleApiServiceLogTest extends GuzzleApiServiceTestAbstract
{
    protected function setUp()
    {
        parent::setUp();
        $this->setFixtureResponse("api_responses/logs_ok.txt");
    }

    public function testLogIsPutRequest()
    {
        $this->apiService->log(null, null, null);
        $this->assertGuzzleRequestMethodEquals("PUT");
    }

    public function testLogUsesCorrectRelativePath()
    {
        $this->apiService->log(null, null, null);
        $this->assertGuzzleRequestPathEquals("/v1/logs");
    }

    public function testLogSendsAppKeyInFormData()
    {
        $this->apiService->log(null, null, null);
        $this->assertGuzzleRequestFormFieldValueEquals("app_key", $this->appKey);
    }

    public function testLogSendsEncryptedSecretKeyInFormData()
    {
        $this->apiService->log(null, null, null);
        $this->assertGuzzleRequestFormFieldValueEquals("secret_key", base64_encode($this->rsaEncrypted));
    }

    public function testLogUsesSecretKeyAndCurrentTimeInLaunchKeyTimeFormatForEncryptedSecretKey()
    {
        $before = new \DateTime();
        $this->apiService->log(null, null, null);
        $after = new \DateTime();
        $this->assertLastItemRsaEncryptedWasValidSecretKey($before, $after);
    }

    public function testLogSendsSignatureInFormData()
    {
        $this->apiService->log(null, null, null);
        $this->assertGuzzleRequestFormFieldValueEquals("signature", $this->signed);
    }

    public function testLogSendsActionInFormData()
    {
        $this->apiService->log(null, "Action Verb", null);
        $this->assertGuzzleRequestFormFieldValueEquals("action", "Action Verb");
    }

    public function testLogStatusAsStringTrueWhenTrueInFormData()
    {
        $this->apiService->log(null, null, true);
        $this->assertGuzzleRequestFormFieldValueEquals("status", "True");
    }

    public function testLogStatusAsStringFalseWhenFalseInFormData()
    {
        $this->apiService->log(null, null, false);
        $this->assertGuzzleRequestFormFieldValueEquals("status", "False");
    }

    public function testLogSendsAuthRequestInFormData()
    {
        $this->apiService->log("Auth Request", null, null);
        $this->assertGuzzleRequestFormFieldValueEquals("auth_request", "Auth Request");
    }

    public function testLogThrowsInvalidResponseErrorWhenBodyIsNotParseable()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidResponseError');
        $this->setFixtureResponse("api_responses/invalid.txt");
        $this->apiService->log(null, null, null);
    }


    public function testLogThrowsCommunicationErrorOnServerError()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        $this->setFixtureResponse("api_responses/server_error.txt");
        $this->apiService->log(null, null, null);
    }

    public function testLogThrowsInvalidRequestOn400()
    {
        $this->setExpectedException(
            '\LaunchKey\SDK\Service\Exception\InvalidRequestError',
            '{"username":"Invalid character used. Do not use &gt; &lt; ) ( @ : ; &amp;"}',
            40421
        );
        $this->setFixtureResponse("api_responses/request_error.txt");
        $this->apiService->log(null, null, null);
    }
}
