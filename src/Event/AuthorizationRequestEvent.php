<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Event;


use LaunchKey\SDK\Domain\AuthorizationRequest;

/**
 * Event dispatched after the SDK initiates a LaunchKey authorization request
 * @package LaunchKey\SDK\Event
 */
class AuthorizationRequestEvent extends AbstractEvent
{
    const NAME = "launchkey.authorization.request";

    /**
     * @var AuthorizationRequest
     */
    private $authorizationRequest;

    public function __construct(AuthorizationRequest $authorizationRequest)
    {
        $this->authorizationRequest = $authorizationRequest;
    }

    /**
     * @return AuthorizationRequest
     */
    public function getAuthorizationRequest()
    {
        return $this->authorizationRequest;
    }
}
