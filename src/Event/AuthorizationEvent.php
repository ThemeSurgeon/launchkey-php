<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that will be fired when an auth request has been completed
 *
 * Class AuthorizationEvent
 * @package LaunchKey\Event
 */
class AuthorizationEvent extends Event
{
    const NAME = "launchkey.authorization";

    private $authRequestId;

    private $status;

    public function __construct($authRequestId, $status)
    {
        $this->authRequestId = $authRequestId;
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getAuthRequestId()
    {
        return $this->authRequestId;
    }

    /**
     * @return string One the \LaunchKey\Domain\AuthStatus constants
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function setName($name)
    {
        throw new \LogicException("setName is not allowed");
    }

    public function getName()
    {
        return static::NAME;
    }
}
