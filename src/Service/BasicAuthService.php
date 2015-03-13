<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;


use LaunchKey\SDK\Domain\AuthRequest;
use LaunchKey\SDK\Domain\AuthResponse;
use LaunchKey\SDK\Domain\DeOrbitCallback;
use LaunchKey\SDK\Domain\DeOrbitRequest;
use LaunchKey\SDK\Event\AuthRequestEvent;
use LaunchKey\SDK\Event\AuthResponseEvent;
use LaunchKey\SDK\Event\DeOrbitCallbackEvent;
use LaunchKey\SDK\Event\DeOrbitRequestEvent;
use LaunchKey\SDK\EventDispatcher\EventDispatcher;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class BasicAuthService implements AuthService
{

    /**
     * @var ApiService
     */
    private $apiService;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $appKey;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @param string $appKey App key from dashboard
     * @param string $secretKey Application secret key from dashboard
     * @param ApiService $apiService
     * @param PingService $pingService
     * @param EventDispatcher $eventDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(
        $appKey,
        $secretKey,
        ApiService $apiService,
        PingService $pingService,
        EventDispatcher $eventDispatcher,
        LoggerInterface $logger = null
    )
    {
        $this->appKey = $appKey;
        $this->secretKey = $secretKey;
        $this->apiService = $apiService;
        $this->pingService = $pingService;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * Authorize a transaction for the provided username
     *
     * @param string $username LaunchKey username, user hash, or internal identifier for the user
     * @return AuthRequest
     */
    public function authorize($username)
    {
        return $this->auth($username, false);
    }

    /**
     * Request a user session for the provided username
     *
     * @param string $username LaunchKey username, user hash, or internal identifier for the user
     * @return AuthResponse
     */
    public function authenticate($username)
    {
        return $this->auth($username, true);
    }

    /**
     * Get the status of a previous authorize or authenticate.  This method can be used after a user has
     * successfully authenticate to determine if the user has submitted a de-orbit request and authorization
     * for a session has been revoked.
     *
     * @param string $authRequestId ID from the AuthResponse object returned from a previous authorize
     * or authenticate call.
     * @return AuthResponse
     */
    public function getStatus($authRequestId)
    {
        $publicKey = $this->pingService->ping()->getPublicKey();
        if ($this->logger) $this->logger->debug("Sending poll request", array("authRequestId" => $authRequestId));
        $authResponse = $this->apiService->poll($authRequestId, $publicKey);
        if ($this->logger) $this->logger->debug("poll response received", array("response" => $authResponse));
        try {
            $this->processAuthResponse($authResponse, $publicKey);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Error logging Authentication true", array("Exception" => $e));
            }
        }
        return $authResponse;
    }

    /**
     * Revoke the authorization for a session.  This method is to be called after a user is logged out of the
     * application in order to update the LaunchKey or white label application of the status of the authenticated
     * session.
     *
     * @param $authRequestId
     * @return null
     */
    public function deOrbit($authRequestId)
    {
        $publicKey = $this->pingService->ping()->getPublicKey();
        if ($this->logger) $this->logger->debug("Logging Revoke true", array("authRequestId" => $authRequestId));
        $this->apiService->log($authRequestId, "Revoke", true, $publicKey);
        $this->eventDispatcher->dispatchEvent(
            DeOrbitRequestEvent::NAME,
            new DeOrbitRequestEvent(new DeOrbitRequest($authRequestId))
        );
    }

    /**
     * Handle a callback request from the LaunchKey Engine.  This data is an associative array of POST data.  This can
     * be the global $_POST array of an array of post data provided by an MVC framework like Zend, Cake, Symfony, etc.
     *
     * @param array $postData
     * @return mixed
     */
    public function handleCallback(array $postData)
    {
        if ($this->logger) $this->logger->debug("Handling callback", array("data" => $postData));
        $response = $this->apiService->handleCallback($postData);
        if ($response instanceof DeOrbitCallback) {
            if ($this->logger) $this->logger->debug("De-orbit callback determined", array("data" => $response));
            $this->eventDispatcher->dispatchEvent(DeOrbitCallbackEvent::NAME, new DeOrbitCallbackEvent($response));
        } elseif ($response instanceof AuthResponse) {
            if ($this->logger) $this->logger->debug("Auth callback determined", array("data" => $response));
            $this->processAuthResponse($response);
        }
    }

    /**
     * @param $username
     * @return AuthRequest
     */
    private function auth($username, $session)
    {
        if ($this->logger) {
            $this->logger->debug(
                "Sending auth request", array("username" => $username, "session" => $session)
            );
        }
        $authRequest = $this->apiService->auth(
            $username,
            $session,
            $this->appKey,
            $this->secretKey,
            $this->pingService->ping()->getPublicKey()
        );
        $this->eventDispatcher->dispatchEvent(AuthRequestEvent::NAME, new AuthRequestEvent($authRequest));
        if ($this->logger) {
            $this->logger->debug("auth response received", array("response" => $authRequest));
        }
        return $authRequest;
    }

    /**
     * @param AuthResponse $authResponse
     * @param string $publicKey
     */
    private function processAuthResponse(AuthResponse $authResponse, $publicKey = null)
    {
        $this->eventDispatcher->dispatchEvent(AuthResponseEvent::NAME, new AuthResponseEvent($authResponse));
        if ($authResponse->isAuthorized()) {
            if ($this->logger) {
                $this->logger->debug("Logging Authenticate true");
            }
            $publicKey = $publicKey ? $publicKey : $this->pingService->ping()->getPublicKey();
            $this->apiService->log($authResponse->getAuthRequestId(), "Authenticate", true, $publicKey);
        }
    }
}
