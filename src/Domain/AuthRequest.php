<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Domain;

/**
 * Authorization request value object
 *
 * @package LaunchKey\SDK\Domain
 */
class AuthRequest
{
    /**
     * @var string User name or internal identifier for the authorization request.  Internal identifiers are used for
     * white label applications.
     */
    private $username;

    /**
     * @var bool Is the authorization request for a user session as opposed to a transaction.  Defaults to FALSE.
     */
    private $userSession;

    /**
     * @param string $username  LaunchKey user name for the authorization request.  Internal identifiers are used for
     * white label applications.
     * @param bool $userSession Is the authorization request for a user session as opposed to a transaction.  Defaults
     * to FALSE.
     */
    function __construct($username, $userSession = false)
    {
        $this->username = $username;
        $this->userSession = $userSession;
    }

    /**
     * @return string User name or internal identifier for the authorization request.  Internal identifiers are used for
     * white label applications.
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return boolean Is the authorization request for a user session as opposed to a transaction.
     */
    public function isUserSession()
    {
        return $this->userSession;
    }
}
