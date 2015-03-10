<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Event;


use LaunchKey\SDK\Domain\DeOrbitResponse;

/**
 * Event dispatched after the SDK receives a LaunchKey deorbit response
 *
 * @package LaunchKey\SDK\Event
 */
class DeOrbitResponseEvent
{
    const NAME = "launchkey.de-orbit.response";

    /**
     * @var DeOrbitResponse
     */
    private $deOrbitResponse;

    function __construct(DeOrbitResponse $deOrbitResponse)
    {
        $this->deOrbitResponse = $deOrbitResponse;
    }

    /**
     * @return DeOrbitResponse
     */
    public function getDeOrbitResponse()
    {
        return $this->deOrbitResponse;
    }
}
