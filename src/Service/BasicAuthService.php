<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;


use LaunchKey\SDK\Domain\AuthorizationResponse;
use LaunchKey\SDK\EventDispatcher\EventDispatcher;

class BasicAuthService implements AuthService
{
    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * @var ApiService
     */
    private $httpService;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param CryptService $cryptService
     * @param ApiService $httpService
     * @param PingService $pingService
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(
        CryptService $cryptService,
        ApiService $httpService,
        PingService $pingService,
        EventDispatcher $eventDispatcher
    )
    {
        $this->cryptService = $cryptService;
        $this->httpService = $httpService;
        $this->pingService = $pingService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Authorize a transaction for the provided username
     *
     * @param string $username LaunchKey username, user hash, or internal identifier for the user
     * @return AuthorizationResponse
     */
    public function authorize($username)
    {
        // TODO: Implement authorize() method.
    }

    /**
     * Request a user session for the provided username
     *
     * @param string $username LaunchKey username, user hash, or internal identifier for the user
     * @return AuthorizationResponse
     */
    public function authenticate($username)
    {
        // TODO: Implement authenticate() method.
    }

    /**
     * Get the status of a previous authorize or authenticate.  This method can be used after a user has
     * successfully authenticate to determine if the user has submitted a de-orbit request and authorization
     * for a session has been revoked.
     *
     * @param string $authRequestId ID from the AuthorizationResponse object returned from a previous authorize
     * or authenticate call.
     * @return AuthorizationResponse
     */
    public function getStatus($authRequestId)
    {
        // TODO: Implement getStatus() method.
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
        // TODO: Implement deOrbit() method.
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
        // TODO: Implement handleCallback() method.
    }
}
