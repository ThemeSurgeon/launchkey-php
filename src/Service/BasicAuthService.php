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
     * @param ApiService $apiService
     * @param EventDispatcher $eventDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(
        ApiService $apiService,
        EventDispatcher $eventDispatcher,
        LoggerInterface $logger = null
    )
    {
        $this->apiService = $apiService;
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
        $this->debugLog("Sending poll request", array("authRequestId" => $authRequestId));
        $authResponse = $this->apiService->poll($authRequestId);
        $this->debugLog("poll response received", array("response" => $authResponse));
        try {
            $this->processAuthResponse($authResponse);
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
        $this->debugLog("Logging Revoke true", array("authRequestId" => $authRequestId));
        $this->apiService->log($authRequestId, "Revoke", true);
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
     * @return AuthResponse|DeOrbitCallback
     */
    public function handleCallback(array $postData)
    {
        $this->debugLog("Handling callback", array("data" => $postData));
        $response = $this->apiService->handleCallback($postData);
        if ($response instanceof DeOrbitCallback) {
            $this->debugLog("De-orbit callback determined", array("data" => $response));
            $this->eventDispatcher->dispatchEvent(DeOrbitCallbackEvent::NAME, new DeOrbitCallbackEvent($response));
        } elseif ($response instanceof AuthResponse) {
            $this->debugLog("Auth callback determined", array("data" => $response));
            $this->processAuthResponse($response);
        }
        return $response;
    }

    /**
     * @param $username
     * @return AuthRequest
     */
    private function auth($username, $session)
    {
        $this->debugLog("Sending auth request", array("username" => $username, "session" => $session));
        $authRequest = $this->apiService->auth($username, $session);
        $this->eventDispatcher->dispatchEvent(AuthRequestEvent::NAME, new AuthRequestEvent($authRequest));
        $this->debugLog("auth response received", array("response" => $authRequest));
        return $authRequest;
    }

    /**
     * @param AuthResponse $authResponse
     */
    private function processAuthResponse(AuthResponse $authResponse)
    {
        $this->eventDispatcher->dispatchEvent(AuthResponseEvent::NAME, new AuthResponseEvent($authResponse));
        if ($authResponse->isAuthorized()) {
            if ($this->logger) {
                $this->logger->debug("Logging Authenticate true");
            }
            $this->apiService->log($authResponse->getAuthRequestId(), "Authenticate", true);
        }
    }

    private function debugLog($message, $context)
    {
        if ($this->logger) $this->logger->debug($message, $context);
    }
}
