<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;

use LaunchKey\SDK\Domain\PingResponse;

/**
 * Ping service interface
 *
 * @package LaunchKey\SDK\Service
 */
interface PingService
{
    /**
     * @return PingResponse
     */
    public function ping();
}
