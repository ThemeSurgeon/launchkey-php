<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Domain;


class DeOrbitResponse
{
    /**
     * @var \DateTime When the de-orbit occurred
     */
    private $deOrbitTime;

    /**
     * @var string The user hash the requested the de-orbit.  Should be null, if the response was initiated by the
     * LaunchKey SDK and not a LaunchKey engine callback.
     */
    private $userHash;

    /**
     * @param \DateTime|null $deOrbitTime When the de-orbit occurred.  Defaults to the current date/time.
     * @param string|null $userHash The user hash the requested the de-orbit.  Should be null, if the response was
     * initiated by the LaunchKey SDK and not a LaunchKey engine callback.
     */
    public function __construct(\DateTime $deOrbitTime = null, $userHash = null)
    {
        $this->deOrbitTime = $deOrbitTime ?: new \DateTime();
        $this->userHash = $userHash;
    }

    /**
     *  Get when the de-orbit occurred
     *
     * @return \DateTime
     */
    public function getDeOrbitTime()
    {
        return $this->deOrbitTime;
    }

    /**
     * Get the user hash the requested the de-orbit.  Should be null, if the response was
     * initiated by the LaunchKey SDK and not a LaunchKey engine callback.
     *
     * @return string
     */
    public function getUserHash()
    {
        return $this->userHash;
    }
}
