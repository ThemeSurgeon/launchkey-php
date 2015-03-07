<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Domain;

/**
 * Collection of valid authorization statuses
 *
 * Interface AuthStatus
 * @package LaunchKey\SDK\Domain
 */
interface AuthStatus
{
    /**
     * The authorization request has been completed and was authorized
     */
    const AUTHORIZED = "authorized";

    /**
     * The authorization request has been completed and was not authorized
     */
    const UNAUTHORIZED = "unauthorized";

    /**
     * The authorization request is still pending
     */
    const PENDING = "pending";

    /**
     * The authorization request was started but has not been verified
     */
    const STARTED = "started";
}
