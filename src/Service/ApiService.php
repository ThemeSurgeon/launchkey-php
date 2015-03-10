<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;


use LaunchKey\SDK\Domain\AuthorizationRequest;
use LaunchKey\SDK\Domain\AuthorizationResponse;

interface ApiService
{
    /**
     * Perform a ping request
     * @return PingResponse
     */
    public function ping();

    /**
     * Perform an "auth" request
     *
     * @param string $username Username to authorize
     * @param bool $session Is the request for a user session and not a transaction
     * @return AuthorizationRequest
     */
    public function auth($username, $session);

    /**
     * Poll to see if the auth request is completed and approved/denied
     *
     * @param string $authRequest auth_request returned from a postAuth call
     * @return AuthorizationResponse
     */
    public function poll($authRequest);

    /**
     * Update the LaunchKey Engine with the current status of the auth request or user session
     *
     * @param $action
     * @param $status
     */
    public function log($action, $status);
}
