<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Domain;


class PingResponse
{
    /**
     * @var \DateTime
     */
    private $launchKeyTime;

    /**
     * @var String
     */
    private $publicKey;

    /**
     * @param \DateTime $launchKeyTime The date/time in the default time zone based omn the launchkey_time returned by ping
     * response.
     * @param $publicKey The public key returned by the ping response
     */
    function __construct(\DateTime $launchKeyTime, $publicKey)
    {
        $this->launchKeyTime = $launchKeyTime;
        $this->publicKey = $publicKey;
    }

    /**
     * @return \DateTime
     */
    public function getLaunchKeyTime()
    {
        return $this->launchKeyTime;
    }

    /**
     * @return String
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }
}
