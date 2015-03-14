<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;


use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Url;
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

class GuzzleApiService implements ApiService
{
    const LAUNCHKEY_DATE_FORMAT = "Y-m-d H:i:s";

    const CACHE_KEY_PUBLIC_KEY = "launchkey-public-key-cache";

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
        $this->appKey = $appKey;
        $this->secretKey = $secretKey;
        $this->guzzleClient = $guzzleClient;
        $this->cryptService = $cryptService;
        $this->cache = $cache;
        $this->publicKeyTTL = $publicKeyTTL;
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
     * @return AuthRequest
     * @throws CommunicationError If there was an error communicating with the endpoint
     * @throws InvalidCredentialsError If the credentials supplied to the endpoint were invalid
     * @throws InvalidRequestError If the endpoint proclaims the request invalid
     */
    public function auth($username, $session)
    {
        $encryptedSecretKey = $this->getEncryptedSecretKey();
        $request = $this->guzzleClient->post("/auths")
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
        $request = $this->guzzleClient->get("/poll")
            ->addPostFields(array(
                "app_key" => $this->appKey,
                "secret_key" => base64_encode($encryptedSecretKey),
                "signature" => $this->cryptService->sign($encryptedSecretKey),
                "auth_request" => $authRequest
            ));
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
     * @return  If there was an error communicating with the endpoint
     */
    public function log($authRequest, $action, $status)
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
    public function createWhiteLabelUser($identifier)
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
    private function debugLog($message, array $context = array())
    {
        if ($this->logger) {
            $this->logger->debug($message, $context);
        }
    }

    /**
     * @param $message
     * @param array $context
     */
    private function errorLog($message, array $context)
    {
        if ($this->logger) {
            $this->logger->error($message, $context);
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

        if (!$data) {
            $message = "Unable to parse body as JSON";
            if (function_exists("json_last_error_msg")) $message += ": " . json_last_error_msg();
            throw new InvalidResponseError($message);
        }
        return $data;
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

        $original = error_reporting(E_ERROR);
        $data = json_decode($response->getBody(true), true);
        error_reporting($original);
        if (!$data) {
            $msg = "Unable to parse body as JSON";
            if (function_exists("json_last_error_msg")) {
                $msg += ": " . json_last_error_msg();
            }
            throw new InvalidResponseError($msg);
        }
        return $data;
    }

    /**
     * Get the current RSA public key for the LaunchKey API
     *
     * @return string
     */
    private function getPublicKey()
    {
        $response = null;
        try {
            $publicKey = $this->cache->get(static::CACHE_KEY_PUBLIC_KEY);
        } catch (\Exception $e) {
            $this->errorLog("An error occurred on a cache get", array("key" => static::CACHE_KEY_PUBLIC_KEY, "Exception" => $e));
        }

        if ($publicKey) {
            $this->debugLog("Public key cache hit", array("key" => static::CACHE_KEY_PUBLIC_KEY));
        } else {
            $this->debugLog("Public key cache miss", array("key" => static::CACHE_KEY_PUBLIC_KEY));
            $response = $this->ping();
            $publicKey = $response->getPublicKey();
            try {
                $this->cache->set(static::CACHE_KEY_PUBLIC_KEY, $publicKey, $this->publicKeyTTL);
                $this->debugLog("Public key saved to cache");
            } catch (\Exception $e) {
                $this->errorLog(
                    "An error occurred on a cache set",
                    array("key" => static::CACHE_KEY_PUBLIC_KEY, "value" => $publicKey, "Exception" => $e)
                );
            }
        }
        return $publicKey;
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
}
