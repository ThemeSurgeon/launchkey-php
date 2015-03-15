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
use LaunchKey\SDK\Cache\Cache;
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

class GuzzleApiService extends PublicKeyCachingAbstractApiService implements ApiService
{
    const LAUNCHKEY_DATE_FORMAT = "Y-m-d H:i:s";

    /**
     * @var string
     */
    private $appKey;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var ClientInterface
     */
    private $guzzleClient;

    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \DateTimeZone
     */
    private $launchKeyDatTimeZone;

    /**
     * @param string $appKey
     * @param string $secretKey
     * @param ClientInterface $guzzleClient
     * @param CryptService $cryptService
     * @param Cache $cache Cache implementation to be used for caching public keys
     * @param int $publicKeyTTL Number of seconds a public key should live in the cache
     * @param LoggerInterface $logger
     */
    public function __construct(
        $appKey,
        $secretKey,
        ClientInterface $guzzleClient,
        CryptService $cryptService,
        Cache $cache,
        $publicKeyTTL,
        LoggerInterface $logger = null
    ) {
        parent::__construct($cache, $publicKeyTTL, $logger);
        $this->appKey = $appKey;
        $this->secretKey = $secretKey;
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
            $request = $this->guzzleClient->get("/v1/ping");
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
     * @return AuthRequest
     * @throws CommunicationError If there was an error communicating with the endpoint
     * @throws InvalidCredentialsError If the credentials supplied to the endpoint were invalid
     * @throws InvalidRequestError If the endpoint proclaims the request invalid
     */
    public function auth($username, $session)
    {
        $encryptedSecretKey = $this->getEncryptedSecretKey();
        $request = $this->guzzleClient->post("/v1/auths")
            ->addPostFields(array(
                "app_key" => $this->appKey,
                "secret_key" => base64_encode($encryptedSecretKey),
                "signature" => $this->cryptService->sign($encryptedSecretKey),
                "username" => $username,
                "session" => $session ? 1 : 0,
                "user_push_id" => 1
            ));
        $data = $this->sendRequest($request);
        return new AuthRequest($username, $session, $data["auth_request"]);
    }

    /**
     * Poll to see if the auth request is completed and approved/denied
     *
     * @param string $authRequest auth_request returned from an auth call
     * @return AuthResponse
     * @throws CommunicationError If there was an error communicating with the endpoint
     * @throws InvalidCredentialsError If the credentials supplied to the endpoint were invalid
     * @throws InvalidRequestError If the endpoint proclaims the request invalid
     */
    public function poll($authRequest)
    {
        $encryptedSecretKey = $this->getEncryptedSecretKey();
        $request = $this->guzzleClient->post("/v1/poll")
            ->addPostFields(array(
                "app_key" => $this->appKey,
                "secret_key" => base64_encode($encryptedSecretKey),
                "signature" => $this->cryptService->sign($encryptedSecretKey),
                "auth_request" => $authRequest
            ));
        $request->getQuery()->add("METHOD", "GET");
        try {
            $data = $this->sendRequest($request);
            $auth = json_decode($this->cryptService->decryptRSA($data['auth']), true);
            $response = new AuthResponse(
                true,
                $auth["auth_request"],
                $data["user_hash"],
                $data["organization_user"],
                $data["user_push_id"],
                $auth["device_id"],
                $auth["response"] == "true"
            );
        } catch (InvalidRequestError $e) {
            if ($e->getCode() == 70403) {
                $response = new AuthResponse();
            } else {
                throw $e;
            }
        }
        return $response;

    }

    /**
     * Update the LaunchKey Engine with the current status of the auth request or user session
     *
     * @param string $authRequest auth_request returned from an auth call
     * @param string $action Action to log.  i.e. Authenticate, Revoke, etc.
     * @param bool $status
     * @return null
     * @throws CommunicationError If there was an error communicating with the endpoint
     * @throws InvalidCredentialsError If the credentials supplied to the endpoint were invalid
     * @throws InvalidRequestError If the endpoint proclaims the request invalid
     */
    public function log($authRequest, $action, $status)
    {
        $encryptedSecretKey = $this->getEncryptedSecretKey();
        $request = $this->guzzleClient->put("/v1/logs")
            ->addPostFields(array(
                "app_key" => $this->appKey,
                "secret_key" => base64_encode($encryptedSecretKey),
                "signature" => $this->cryptService->sign($encryptedSecretKey),
                "auth_request" => $authRequest,
                "action" => $action,
                "status" => $status ? "True" : "False"
            ));
        $this->sendRequest($request);
    }

    /**
     * Create a white label user with the following identifier
     *
     * @param string $identifier Unique and permanent identifier for the user in the white label application.  This identifier
     * will be used in all future communications regarding this user.  As such, it cannot ever change.
     *
     * @return WhiteLabelUser
     * @throws CommunicationError If there was an error communicating with the endpoint
     * @throws InvalidCredentialsError If the credentials supplied to the endpoint were invalid
     * @throws InvalidRequestError If the endpoint proclaims the request invalid
     * @throws InvalidResponseError If the encrypted data is not valid JSON
     */
    public function createWhiteLabelUser($identifier)
    {
        $body = json_encode(array(
            "app_key" => $this->appKey,
            "secret_key" => base64_encode($this->getEncryptedSecretKey()),
            "identifier" => $identifier
        ));
        $request = $this->guzzleClient->post("/v1/users")
           ->setBody($body, "application/json");
        $request->getQuery()->add("signature", $this->cryptService->sign($body));
        $data = $this->sendRequest($request);
        $cipher = $this->cryptService->decryptRSA($data["cipher"]);
        $key = substr($cipher, 0, strlen($cipher) - 16);
        $iv = substr($cipher, -16);
        $userJsonData = $this->cryptService->decryptAES($data["data"], $key, $iv);
        $userData = $this->jsonDecodeData($userJsonData);
        if (!$userData) {
            throw new InvalidResponseError("Response data is not valid JSON when decrypted");
        }
        return new WhiteLabelUser(
            $userData["lk_identifier"],
            $userData["qrcode"],
            $userData["code"]
        );
    }

    /**
     * Handle a LaunchKey engine callback with the provided post data
     *
     * @param array $postData
     * @return AuthResponse|DeOrbitCallback Object generated by processing the provided $postData
     * @throws InvalidRequestError for auth response when  auth_request values from post data and decrypted auth data do
     * not match or for deorbit request when the signature is invalid
     * @throws UnknownCallbackActionError when the callback type could not be determined by the data provided
     */
    public function handleCallback(array $postData)
    {
        $this->debugLog("Starting handle of callback", $postData);
        if (isset($postData["auth"])) {
            $this->debugLog("Callback determined to be auth callback");
            $response = $this->handleAuthCallback($postData);
        } elseif (isset($postData["deorbit"])) {
            $this->debugLog("Callback determined to be deorbit callback");
            $response = $this->handDeOrbitCallback($postData);
        } else {
            throw new UnknownCallbackActionError("Could not determine auth callback action");
        }
        return $response;
    }

    /**
     * @param array $postData
     * @return AuthResponse
     * @throws InvalidRequestError When auth_request values from post data and decrypted auth data do not match.
     */
    private function handleAuthCallback(array $postData)
    {
        $auth = json_decode($this->cryptService->decryptRSA($postData["auth"]), true);
        if ($postData["auth_request"] !== $auth["auth_request"]) {
            throw new InvalidRequestError("Invalid auth callback auth_request values did not match");
        }
        $response = new AuthResponse(
            true,
            $auth["auth_request"],
            $postData["user_hash"],
            isset($postData["organization_user"]) ? $postData["organization_user"] : null,
            $postData["user_push_id"],
            $auth["device_id"],
            $auth["response"] == "true"
        );
        return $response;
    }

    private function handDeOrbitCallback(array $postData)
    {
        if (!$this->cryptService->verifySignature($postData["signature"], $postData["deorbit"], $this->getPublicKey())) {
            throw new InvalidRequestError("Invalid signature for de-orbit callback");
        }
        $data = json_decode($postData["deorbit"], true);
        $lkTime = \DateTime::createFromFormat(
            static::LAUNCHKEY_DATE_FORMAT,
            $data["launchkey_time"],
            $this->launchKeyDatTimeZone
        );
        return new DeOrbitCallback($lkTime, $data["user_hash"]);
    }

    /**
     * @param $message
     * @param array $context
     */
    private function debugLog($message, array $context = array())
    {
        if ($this->logger) {
            $this->logger->debug($message, $context);
        }
    }

    private function getLaunchKeyDate($launchkeyTimeString)
    {
        return \DateTime::createFromFormat(static::LAUNCHKEY_DATE_FORMAT, $launchkeyTimeString, $this->launchKeyDatTimeZone);
    }

    private function getLaunchKeyDateString()
    {
        return date_create(null, $this->launchKeyDatTimeZone)->format(static::LAUNCHKEY_DATE_FORMAT);
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

        $data = $this->jsonDecodeData($response->getBody(true));
        if (!$data) {
            $msg = "Unable to parse body as JSON";
            // @codeCoverageIgnoreStart
            // json_last_error_msg does not exist in all supported versions of PHP but will be helpful if there
            if (function_exists("json_last_error_msg")) {
                $msg += ": " . json_last_error_msg();
            }
            // @codeCoverageIgnoreEnd
            throw new InvalidResponseError($msg);
        }
        // If debug response with data in the "response" attribute return that
        return isset($data["response"]) ? $data["response"] : $data;
    }

    /**
     * @return string
     */
    private function getEncryptedSecretKey()
    {
        $encryptedSecretKey = $this->cryptService->encryptRSA(
            json_encode(array("secret" => $this->secretKey, "stamped" => $this->getLaunchKeyDateString())),
            $this->getPublicKey(),
            false
        );
        return $encryptedSecretKey;
    }

    /**
     * @param string $json
     * @return array
     */
    private function jsonDecodeData($json)
    {
        $original = error_reporting(E_ERROR);
        $data = json_decode($json, true);
        error_reporting($original);
        return $data;
    }
}
