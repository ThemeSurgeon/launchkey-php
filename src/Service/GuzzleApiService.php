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
use LaunchKey\SDK\Service\Exception\ExpiredAuthRequestError;
use LaunchKey\SDK\Service\Exception\InvalidCredentialsError;
use LaunchKey\SDK\Service\Exception\InvalidRequestError;
use LaunchKey\SDK\Service\Exception\InvalidResponseError;
use LaunchKey\SDK\Service\Exception\LaunchKeyEngineError;
use LaunchKey\SDK\Service\Exception\NoPairedDevicesError;
use LaunchKey\SDK\Service\Exception\NoSuchUserError;
use LaunchKey\SDK\Service\Exception\RateLimitExceededError;
use LaunchKey\SDK\Service\Exception\UnknownCallbackActionError;
use Psr\Log\LoggerInterface;

/**
 * ApiService implementation utilizing Guzzle3 as the HTTP client
 *
 * @package LaunchKey\SDK\Service
 */
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
     * @throws InvalidRequestError If the endpoint proclaims the request invalid
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
     * @throws NoPairedDevicesError If the account for the provided username has no paired devices with which to respond
     * @throws NoSuchUserError If the username provided does not exist
     * @throws RateLimitExceededError If the same username is requested to often and exceeds the rate limit
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
     * @throws ExpiredAuthRequestError If the auth request has expired
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
     * @throws ExpiredAuthRequestError If the auth request has expired
     * @throws LaunchKeyEngineError If the LaunchKey cannot apply the request auth request, action, status
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
            $userData["qrcode"],
            $userData["code"]
        );
    }

    /**
     * Handle a LaunchKey engine callback with the query parameters from the callback POST call
     *
     * @param array $queryParameters Query parameters from the callback POST call
     * @return AuthResponse|DeOrbitCallback Object generated by processing the provided $queryParameters
     * @throws InvalidRequestError for auth response when  auth_request values from post data and decrypted auth data do
     * not match or for deorbit request when the signature is invalid
     * @throws UnknownCallbackActionError when the callback type could not be determined by the data provided
     */
    public function handleCallback(array $queryParameters)
    {
        $this->debugLog("Starting handle of callback", $queryParameters);
        if (isset($queryParameters["auth"])) {
            $this->debugLog("Callback determined to be auth callback");
            $response = $this->handleAuthCallback($queryParameters);
        } elseif (isset($queryParameters["deorbit"])) {
            $this->debugLog("Callback determined to be deorbit callback");
            $response = $this->handDeOrbitCallback($queryParameters);
        } else {
            throw new UnknownCallbackActionError("Could not determine auth callback action");
        }
        return $response;
    }

    /**
     * @param array $queryParameters
     * @return AuthResponse
     * @throws InvalidRequestError When auth_request values from post data and decrypted auth data do not match.
     */
    private function handleAuthCallback(array $queryParameters)
    {
        $auth = json_decode($this->cryptService->decryptRSA($queryParameters["auth"]), true);
        if ($queryParameters["auth_request"] !== $auth["auth_request"]) {
            throw new InvalidRequestError("Invalid auth callback auth_request values did not match");
        }
        $response = new AuthResponse(
            true,
            $auth["auth_request"],
            $queryParameters["user_hash"],
            isset($queryParameters["organization_user"]) ? $queryParameters["organization_user"] : null,
            $queryParameters["user_push_id"],
            $auth["device_id"],
            $auth["response"] == "true"
        );
        return $response;
    }

    private function handDeOrbitCallback(array $queryParameters)
    {
        if (!$this->cryptService->verifySignature($queryParameters["signature"], $queryParameters["deorbit"], $this->getPublicKey())) {
            throw new InvalidRequestError("Invalid signature for de-orbit callback");
        }
        $data = json_decode($queryParameters["deorbit"], true);
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

    /**
     * @param RequestInterface $request
     * @return array
     * @throws CommunicationError
     * @throws ExpiredAuthRequestError
     * @throws InvalidCredentialsError
     * @throws InvalidRequestError
     * @throws InvalidResponseError
     * @throws LaunchKeyEngineError
     * @throws NoPairedDevicesError
     * @throws NoSuchUserError
     * @throws RateLimitExceededError
     */
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

            switch ($code) {
                case "40422":
                case "40423":
                case "40425":
                case "40428":
                case "40429":
                case "40432":
                case "40433":
                case "40434":
                case "40435":
                case "40437":
                case "50442":
                case "50443":
                case "50444":
                case "50445":
                case "50447":
                case "50448":
                case "50449":
                case "50452":
                case "50453":
                case "50454":
                case "50457":
                    throw new InvalidCredentialsError($message, $code, $e);
                    break;
                case "40424":
                    throw new NoPairedDevicesError($message, $code, $e);
                    break;
                case "40426":
                    throw new NoSuchUserError($message, $code, $e);
                    break;
                case "40431":
                case "50451":
                case "70404":
                    throw new ExpiredAuthRequestError($message, $code, $e);
                    break;
                case "40436":
                    throw new RateLimitExceededError($message, $code, $e);
                    break;
                case "50455":
                    throw new LaunchKeyEngineError($message, $code, $e);
                    break;
                default:
                    throw new InvalidRequestError($message, $code, $e);
                    break;
            }
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
