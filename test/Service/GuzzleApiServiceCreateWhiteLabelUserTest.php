<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;

use LaunchKey\SDK\Domain\WhiteLabelUser;

class GuzzleApiServiceCreateWhiteLabelUserTest extends GuzzleApiServiceTestAbstract
{
    private $lkIdentifier = 'LK Identifier';

    private $qrCodeUrl = 'QR Code URL';

    private $code = 'Code';

    public function testIsGetRequest()
    {
        $this->apiService->createWhiteLabelUser(null);
        $this->assertGuzzleRequestMethodEquals("POST");
    }

    public function testUsesCorrectRelativePath()
    {
        $this->apiService->createWhiteLabelUser(null);
        $this->assertGuzzleRequestPathEquals("/v1/users");
    }

    public function testSendsApplicationJsonAsContentType()
    {
        $this->apiService->createWhiteLabelUser(null);
        $this->assertGuzzleRequestHeaderStartsWith("content-type", "application/json");
    }

    public function testSendsSignatureInQueryString()
    {
        \Phake::when($this->cryptService)->sign(\Phake::anyParameters())->thenReturn("Expected Signature");
        $this->apiService->createWhiteLabelUser(null);
        $this->assertGuzzleRequestQueryStringParameterEquals("signature", "Expected Signature");
    }

    public function testSignsEntireBody()
    {
        $this->apiService->createWhiteLabelUser(null);
        $body = (string) $this->assertGuzzleRequest()->getBody();
        \Phake::verify($this->cryptService)->sign($body);
    }

    public function testSendsValidJsonObjectInBody()
    {
        $this->apiService->createWhiteLabelUser("Expected Identifier");
        $decoded = json_decode($this->assertGuzzleRequest()->getBody(), true);
        $this->assertInternalType('array', $decoded);
        return $decoded;
    }

    /** @depends testSendsValidJsonObjectInBody */
    public function testSendsIdentifierInBodyJson(array $jsonDecoded)
    {
        $this->assertArrayHasKey("identifier", $jsonDecoded);
        $this->assertEquals("Expected Identifier", $jsonDecoded["identifier"]);
    }

    /** @depends testSendsValidJsonObjectInBody */
    public function testSendsAppKeyInBodyJson(array $jsonDecoded)
    {
        $this->assertArrayHasKey("app_key", $jsonDecoded);
        $this->assertEquals($this->appKey, $jsonDecoded["app_key"]);
    }

    /** @depends testSendsValidJsonObjectInBody */
    public function testSendsEncryptedSecretKeyInBodyJson(array $jsonDecoded)
    {
        $this->assertArrayHasKey("secret_key", $jsonDecoded);
        $this->assertEquals(base64_encode($this->rsaEncrypted), $jsonDecoded["secret_key"]);
    }

    public function testEncryptedCorrectDataForSecretKey()
    {
        $before = new \DateTime();
        $this->apiService->createWhiteLabelUser(null);
        $after = new \DateTime();
        $this->assertLastItemRsaEncryptedWasValidSecretKey($before, $after);
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

    public function testReturnsWhiteLabelUserAsResponse()
    {
        $actual = $this->apiService->createWhiteLabelUser(null);
        $this->assertInstanceOf('\LaunchKey\SDK\Domain\WhiteLabelUser', $actual);
        return $actual;
    }

    /** @depends testReturnsWhiteLabelUserAsResponse */
    public function testSetsIdentifierCorrectlyOnReturnedWhiteLabelUser(WhiteLabelUser $user)
    {
        $this->assertEquals($this->lkIdentifier, $user->getLaunchKeyIdentifier());
    }

    /** @depends testReturnsWhiteLabelUserAsResponse */
    public function testSetsQrCodeCorrectlyOnReturnedWhiteLabelUser(WhiteLabelUser $user)
    {
        $this->assertEquals($this->qrCodeUrl, $user->getQrCodeUrl());
    }

    /** @depends testReturnsWhiteLabelUserAsResponse */
    public function testSetsCodeCorrectlyOnReturnedWhiteLabelUser(WhiteLabelUser $user)
    {
        $this->assertEquals($this->code, $user->getCode());
    }

    public function testThrowsInvalidResponseErrorWhenBodyIsNotParseable()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidResponseError');
        $this->setFixtureResponse("api_responses/invalid.txt");
        $this->apiService->createWhiteLabelUser(null);
    }

    public function testThrowsInvalidResponseErrorWhenDecryptedDataIsNotJSON()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\InvalidResponseError');
        \Phake::when($this->cryptService)->decryptAES(\Phake::anyParameters())->thenReturn(null);
        $this->apiService->createWhiteLabelUser(null);
    }

    public function testThrowsCommunicationErrorOnServerError()
    {
        $this->setExpectedException('\LaunchKey\SDK\Service\Exception\CommunicationError');
        $this->setFixtureResponse("api_responses/server_error.txt");
        $this->apiService->createWhiteLabelUser(null);
    }

    public function testLogsDebugMessages()
    {
        $this->loggingApiService->createWhiteLabelUser(null);
        \Phake::verify($this->logger, \Phake::atLeast(1))->debug(\Phake::anyParameters());
    }

    public function testThrowsInvalidRequestOn400()
    {
        $this->setExpectedException(
            '\LaunchKey\SDK\Service\Exception\InvalidRequestError',
            '{"username":"Invalid character used. Do not use &gt; &lt; ) ( @ : ; &amp;"}',
            40421
        );
        $this->setFixtureResponse("api_responses/request_error.txt");
        $this->apiService->createWhiteLabelUser(null);
    }

    protected function setUp()
    {
        parent::setUp();
        $this->setFixtureResponse("api_responses/wl_user_create_ok.txt");
        \Phake::when($this->cryptService)
            ->decryptAES(\Phake::anyParameters())
            ->thenReturn('{"lk_identifier": "' . $this->lkIdentifier .
                '", "qrcode": "' . $this->qrCodeUrl . '", "code": "' . $this->code . '"}');
    }
}
