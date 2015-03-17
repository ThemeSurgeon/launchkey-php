<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Service;


use Guzzle\Http\Message\Response;

class GuzzleApiServiceErrorHandlingTest extends GuzzleApiServiceTestAbstract
{
    public function errorCodeExpectedExceptionProvider()
    {
        return array(
            "Auths - Incorrect data for API call"                        => array('40421', '\LaunchKey\SDK\Service\Exception\InvalidRequestError'),
            "Auths - Credentials incorrect for app and app secret"       => array('40422', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Auths - Error verifying app"                                => array('40423', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Auths - No paired devices"                                  => array('40424', '\LaunchKey\SDK\Service\Exception\NoPairedDevicesError'),
            "Auths - Invalid app key"                                    => array('40425', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Auths - No such user"                                       => array('40426', '\LaunchKey\SDK\Service\Exception\NoSuchUserError'),
            "Auths - Signature does not match"                           => array('40428', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Auths - App credentials incorrect"                          => array('40429', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Auths - Authorization expired"                              => array('40431', '\LaunchKey\SDK\Service\Exception\ExpiredAuthRequestError'),
            "Auths - Error checking signature, ensure padding is valid"  => array('40432', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Auths - Signature matches, but error decrypting secret_key" => array('40433', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Auths - Decrypted secret_key, but malformed structure"      => array('40434', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Auths - App disabled"                                       => array('40435', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Auths - Too many authorization attempts"                    => array('40436', '\LaunchKey\SDK\Service\Exception\RateLimitExceededError'),
            "Auths - Datestamp must be in format: %Y-%m-%d %H:%M:%S"     => array('40437', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),

            "Logs - Incorrect data for API call"                         => array('50441', '\LaunchKey\SDK\Service\Exception\InvalidRequestError'),
            "Logs - Credentials incorrect"                               => array('50442', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Logs - Error validating app"                                => array('50443', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Logs - Incorrect data for authorization"                    => array('50444', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Logs - Invalid app key"                                     => array('50445', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Logs - Auth request does not correlate to session"          => array('50446', '\LaunchKey\SDK\Service\Exception\InvalidRequestError'),
            "Logs - App not found"                                       => array('50447', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Logs - Signature does not match"                            => array('50448', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Logs - App credentials incorrect"                           => array('50449', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Logs - Authorization expired"                               => array('50451', '\LaunchKey\SDK\Service\Exception\ExpiredAuthRequestError'),
            "Logs - Error checking signature, ensure padding is valid"   => array('50452', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Logs - Signature matches, but error decrypting secret_key"  => array('50453', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Logs - Decrypted secret_key, but malformed structure"       => array('50454', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),
            "Logs - Log inconsistency, unable to update"                 => array('50455', '\LaunchKey\SDK\Service\Exception\LaunchKeyEngineError'),
            "Logs - Unknown auth request"                                => array('50456', '\LaunchKey\SDK\Service\Exception\InvalidRequestError'),
            "Logs - Datestamp must be in format: %Y-%m-%d %H:%M:%S"      => array('50457', '\LaunchKey\SDK\Service\Exception\InvalidCredentialsError'),

            "Ping - Incorrect data for API call"                         => array('60401', '\LaunchKey\SDK\Service\Exception\InvalidRequestError'),

            "Poll - Incorrect data for API call"                         => array('70401', '\LaunchKey\SDK\Service\Exception\InvalidRequestError'),
            "Poll - There is no pending request"                         => array('70402', '\LaunchKey\SDK\Service\Exception\InvalidRequestError'),
            "Poll - Expired request"                                     => array('70404', '\LaunchKey\SDK\Service\Exception\ExpiredAuthRequestError'),
        );
    }

    /** @dataProvider errorCodeExpectedExceptionProvider */
    public function testPingThrowsExpectedErrors($code, $expectedException)
    {
        $this->setExpectedException($expectedException, '', $code);
        $this->setErrorResponse($code);
        $this->apiService->ping(null);
    }

    /**
     * @param $code
     */
    private function setErrorResponse($code)
    {
        $message = str_replace(
            '"message_code": 40421',
            "\"message_code\": {$code}",
            $this->getFixture("api_responses/request_error.txt")
        );

        $this->guzzleMockPlugin->clearQueue();
        $this->guzzleMockPlugin->addResponse(Response::fromMessage($message));
    }
}
