<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;


use LaunchKey\SDK\Cache\Cache;
use LaunchKey\SDK\Domain\AuthResponse;
use LaunchKey\SDK\Domain\DeOrbitCallback;
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
 * Abstract ApiService implementation that provided the ability to retrieve public keys via API ping and using
 * a Cache implementation to cache the keys.
 *
 * @package LaunchKey\SDK\Service
 */
abstract class AbstractApiService implements ApiService
{
    const CACHE_KEY_PUBLIC_KEY = "launchkey-public-key-cache";

    const LAUNCHKEY_DATE_FORMAT = "Y-m-d H:i:s";

    const LAUNCHKEY_DATE_TZ = "UTC";

    /**
     * @var int
     */
    private $publicKeyTTL;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Cache $cache
     * @param CryptService $cryptService
     * @param string $secretKey
     * @param int $publicKeyTTL
     * @param LoggerInterface $logger
     */
    function __construct(Cache $cache, CryptService $cryptService, $secretKey, $publicKeyTTL = 0, LoggerInterface $logger = null)
    {
        $this->cryptService = $cryptService;
        $this->cache = $cache;
        $this->secretKey = $secretKey;
        $this->publicKeyTTL = $publicKeyTTL;
        $this->logger = $logger;
    }

    /**
     * Get the current RSA public key for the LaunchKey API
     *
     * @return string
     */
    protected function getPublicKey()
    {
        $response = null;
        $publicKey = null;
        try {
            $publicKey = $this->cache->get(static::CACHE_KEY_PUBLIC_KEY);
        } catch (\Exception $e) {
            $this->errorLog(
                "An error occurred on a cache get",
                array("key" => static::CACHE_KEY_PUBLIC_KEY, "Exception" => $e)
            );
        }

        if ($publicKey) {
            $this->debugLog(
                "Public key cache hit",
                array("key" => static::CACHE_KEY_PUBLIC_KEY)
            );
        } else {
            $this->debugLog(
                "Public key cache miss",
                array("key" => static::CACHE_KEY_PUBLIC_KEY)
            );
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

    /**
     * @param array $queryParameters
     *
     * @return DeOrbitCallback
     * @throws InvalidRequestError
     */
    private function handDeOrbitCallback(array $queryParameters)
    {
        if (!$this->cryptService->verifySignature($queryParameters["signature"], $queryParameters["deorbit"], $this->getPublicKey())) {
            throw new InvalidRequestError("Invalid signature for de-orbit callback");
        }
        $data = json_decode($queryParameters["deorbit"], true);
        $lkTime = $this->getLaunchKeyDate($data["launchkey_time"]);
        return new DeOrbitCallback($lkTime, $data["user_hash"]);
    }

    /**
     * @param array $errorResponse
     * @param \Exception $cause
     *
     * @throws ExpiredAuthRequestError
     * @throws InvalidCredentialsError
     * @throws InvalidRequestError
     * @throws LaunchKeyEngineError
     * @throws NoPairedDevicesError
     * @throws NoSuchUserError
     * @throws RateLimitExceededError
     */
    protected function throwExceptionForErrorResponse(array $errorResponse, \Exception $cause = null) {
        if (is_array($errorResponse["message"])) {
            $message = json_encode($errorResponse["message"]);
        } elseif (isset($errorResponse["message"])) {
            $message = $errorResponse["message"];
        } else {
            $message = 'An unknown API Error Occurred';
        }

        if (isset($errorResponse["message_code"])) {
            $code = $errorResponse["message_code"];
        } else {
            $code = 0;
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
                throw new InvalidCredentialsError($message, $code, $cause);
                break;
            case "40424":
                throw new NoPairedDevicesError($message, $code, $cause);
                break;
            case "40426":
                throw new NoSuchUserError($message, $code, $cause);
                break;
            case "40431":
            case "50451":
            case "70404":
                throw new ExpiredAuthRequestError($message, $code, $cause);
                break;
            case "40436":
                throw new RateLimitExceededError($message, $code, $cause);
                break;
            case "50455":
                throw new LaunchKeyEngineError($message, $code, $cause);
                break;
            default:
                throw new InvalidRequestError($message, $code, $cause);
                break;
        }
    }

    /**
     * @param $launchkeyTimeString
     *
     * @return \DateTime
     */
    protected function getLaunchKeyDate($launchkeyTimeString)
    {
        return \DateTime::createFromFormat(
            static::LAUNCHKEY_DATE_FORMAT,
            $launchkeyTimeString,
            new \DateTimeZone(static::LAUNCHKEY_DATE_TZ)
        );
    }

    /**
     * @return string
     */
    protected function getLaunchKeyDateString()
    {
        return date_create(null, new \DateTimeZone(static::LAUNCHKEY_DATE_TZ))->format(static::LAUNCHKEY_DATE_FORMAT);
    }

    /**
     * @return string
     */
    protected function getEncryptedSecretKey()
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
     * @throws InvalidResponseError
     */
    protected function jsonDecodeData($json)
    {
        $original = error_reporting(E_ERROR);
        $data = json_decode($json, true);
        error_reporting($original);
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

        return $data;
    }

    /**
     * @param $message
     * @param array $context
     */
    protected function debugLog($message, array $context = array())
    {
        if ($this->logger) {
            $this->logger->debug($message, $context);
        }
    }

    /**
     * @param $message
     * @param array $context
     */
    protected function errorLog($message, array $context = array())
    {
        if ($this->logger) {
            $this->logger->error($message, $context);
        }
    }
}
