<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Event;

use LaunchKey\SDK\Domain\AuthorizationResponse;

/**
 * Event dispatched after the SDK receives a LaunchKey authorization response from either a poll request or a
 * LaunchKey engine callback
 * @package LaunchKey\SDK\Event
 */
class AuthorizationResponseEvent extends AbstractEvent
{
    const NAME = "launchkey.authorization.response";

    /**
     * @var AuthorizationResponseEvent
     */
    private $authorizationResponse;

    public function __construct(AuthorizationResponse $authorizationResponse)
    {
        $this->authorizationResponse = $authorizationResponse;
    }

    /**
     * @return AuthorizationResponseEvent
     */
    public function getAuthorizationResponse()
    {
        return $this->authorizationResponse;
    }
}
