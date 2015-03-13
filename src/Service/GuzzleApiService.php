<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;


use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use LaunchKey\SDK\Domain\AuthRequest;
use LaunchKey\SDK\Domain\AuthResponse;
use LaunchKey\SDK\Domain\DeOrbitCallback;
use LaunchKey\SDK\Domain\PingResponse;
use LaunchKey\SDK\Domain\WhiteLabelUser;
use LaunchKey\SDK\Service\Exception\CommunicationError;
use LaunchKey\SDK\Service\Exception\InvalidCredentialsError;
use LaunchKey\SDK\Service\Exception\InvalidRequestError;
use LaunchKey\SDK\Service\Exception\InvalidResponseError;
use LaunchKey\SDK\Service\Exception\UnknownCallbackActionError;
use Psr\Log\LoggerInterface;

class GuzzleApiService implements ApiService
{
    const LAUNCHKEY_DATE_FORMAT = "Y-m-d H:i:s";
    /**
     * @var ClientInterface
     */
    private $guzzleClient;

    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * @var \DateTimeZone
     */
    private $launchKeyDatTimeZone;

    /**
     * @param ClientInterface $guzzleClient
     * @param CryptService $cryptService
     * @param LoggerInterface $logger
     */
    public function __construct(ClientInterface $guzzleClient, CryptService $cryptService, LoggerInterface $logger = null) {
        $this->guzzleClient = $guzzleClient;
        $this->cryptService = $cryptService;
        $this->logger = $logger;
        $this->launchKeyDatTimeZone = new \DateTimeZone("UTC");
    }

    /**
     * Perform a ping request
     * @return PingResponse
     * @throws CommunicationError If there was an error communicating with the endpoint
     */
    public function ping()
    {
            $request = $this->guzzleClient->get("/ping");
            $data = $this->sendRequest($request);

        $pingResponse = new PingResponse(
            $this->getLaunchKeyDate($data["launchkey_time"]),
            $data["key"],
            $this->getLaunchKeyDate($data["date_stamp"])
        );
        return $pingResponse;
    }

    /**
     * Perform an "auth" request
     *
     * @param string $username Username to authorize
     * @param bool $session Is the request for a user session and not a transaction
     * @param string $appKey App key for which the username will auth
     * @param string $publicKey The LaunchKey Engine's RSA public key of the current RSA public/private key pair.
     * @return AuthRequest
     * @throws CommunicationError If there was an error communicating with the endpoint
     * @throws InvalidCredentialsError If the credentials supplied to the endpoint were invalid
     * @throws InvalidRequestError If the endpoint proclaims the request invalid
     */
    public function auth($username, $session, $appKey, $secretKey, $publicKey)
    {
        $encryptedSecretKey = $this->cryptService->encryptRSA(
            json_encode(array("secret" => $secretKey, "stamped" => $this->getLaunchKeyDateString())),
            $publicKey,
            false
        );
        try {
            $request = $this->guzzleClient->post("/auths")
                ->addPostFields(array(
                    "app_key" => $appKey,
                    "secret_key" => base64_encode($encryptedSecretKey),
                    "signature" => $this->cryptService->sign($encryptedSecretKey),
                    "username" => $username,
                    "session" => $session ? 1 : 0,
                    "user_push_id" => 1
                ));
            $response = $request->send();
        } catch (ClientErrorResponseException $e) {
            $this->handleClientErrorResponseException($request, $e);
        } catch (ServerErrorResponseException $e) {
            $this->handleServerErrorResponseException($e);
        }
        $data = $this->decodeJsonFromBodyResponse($response);
        return new AuthRequest($username, $session, $data["auth_request"]);
    }

    /**
     * Poll to see if the auth request is completed and approved/denied
     *
     * @param string $authRequest auth_request returned from an auth call
     * @param string $publicKey The LaunchKey Engine's RSA public key of the current RSA public/private key pair.
     * @return AuthResponse
     * @throws CommunicationError If there was an error communicating with the endpoint
     * @throws InvalidCredentialsError If the credentials supplied to the endpoint were invalid
     * @throws InvalidRequestError If the endpoint proclaims the request invalid
     */
    public function poll($authRequest, $publicKey)
    {
        // TODO: Implement poll() method.
    }

    /**
     * Update the LaunchKey Engine with the current status of the auth request or user session
     *
     * @param string $authRequest auth_request returned from an auth call
     * @param string $action Action to log.  i.e. Authenticate, Revoke, etc.
     * @param bool $status
     * @param string $publicKey The LaunchKey Engine's RSA public key of the current RSA public/private key pair.
     * @return  If there was an error communicating with the endpoint
     */
    public function log($authRequest, $action, $status, $publicKey)
    {
        // TODO: Implement log() method.
    }

    /**
     * Create a white label user with the following identifier
     *
     * @param $identifier Unique and permanent identifier for the user in the white label application.  This identifier
     * will be used in all future communications regarding this user.  As such, it cannot ever change.
     * @param string $appKey App key that belongs to the white label group in which the user will be created
     * @param string $publicKey The LaunchKey Engine's RSA public key of the current RSA public/private key pair.
     *
     * @return WhiteLabelUser
     * @throws CommunicationError If there was an error communicating with the endpoint
     * @throws InvalidCredentialsError If the credentials supplied to the endpoint were invalid
     * @throws InvalidRequestError If the endpoint proclaims the request invalid
     */
    public function createWhiteLabelUser($identifier, $appKey, $publicKey)
    {
        // TODO: Implement createWhiteLabelUser() method.
    }

    /**
     * Handle a LaunchKey engine callback with the provided post data
     *
     * @param array $postData
     * @return AuthResponse|DeOrbitCallback Object generated by processing the provided $postData
     * @throws InvalidRequestError when the signature is invalid
     * @throws UnknownCallbackActionError when the callback type could not be determined by the data provided
     */
    public function handleCallback(array $postData)
    {
        // TODO: Implement handleCallback() method.
    }

    /**
     * @param $message
     * @param array $context
     */
    private function debugLog($message, array $context)
    {
        if ($this->logger) {
            $this->logger->debug($message, $context);
        }
    }

    /**
     * @param Response $response
     * @return mixed
     * @throws InvalidResponseError
     */
    private function decodeJsonFromBodyResponse(Response $response)
    {
        $original = error_reporting(E_ERROR);
        $data = json_decode($response->getBody(true), true);
        error_reporting($original);
        if (json_last_error()) {
            throw new InvalidResponseError("Unable to parse body as JSON: " . json_last_error_msg());
        }
        return $data;
    }

    private function getLaunchKeyDate($launchkeyTimeString)
    {
        return \DateTime::createFromFormat(self::LAUNCHKEY_DATE_FORMAT, $launchkeyTimeString, $this->launchKeyDatTimeZone);
    }

    private function getLaunchKeyDateString()
    {
        return date_create(null, $this->launchKeyDatTimeZone)->format(self::LAUNCHKEY_DATE_FORMAT);
    }

    private function sendRequest(RequestInterface $request)
    {
        try {
            $response = $request->send();
            $this->debugLog("Response received", array("response" => $response->getMessage()));
        } catch (ClientErrorResponseException $e) {
            $data = json_decode($request->getResponse()->getBody(), true);
            $message = $e->getMessage();
            $code = $e->getCode();
            if ($data) {
                if (is_array($data["message"])) {
                    $message = json_encode($data["message"]);
                } elseif (isset($data["message"])) {
                    $message = $data["message"];
                }

                if (isset($data["message_code"])) {
                    $code = $data["message_code"];
                }
            }
            throw new InvalidRequestError($message, $code, $e);
        } catch (ServerErrorResponseException $e) {
            throw new CommunicationError("Error performing request", $e->getCode(), $e);
        }

        $original = error_reporting(E_ERROR);
        $data = json_decode($response->getBody(true), true);
        error_reporting($original);
        if (json_last_error()) {
            throw new InvalidResponseError("Unable to parse body as JSON: " . json_last_error_msg());
        }
        return $data;
    }
}
