<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;

/**
 * Interface for objects that are ping service aware
 *
 * @package LaunchKey\SDK\Service
 */
interface PingServiceAware
{
    /**
     * Set the ping service
     *
     * @param PingService $pingService
     */
    public function setPingService(PingService $pingService);
}
