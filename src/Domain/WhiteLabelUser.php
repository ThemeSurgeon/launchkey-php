<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Domain;

/**
 * Domain value object representing a white label user
 *
 * Class WhiteLabelUser
 * @package LaunchKey\SDK\Domain
 */
class WhiteLabelUser
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $qrCodeUrl;

    /**
     * @var string
     */
    private $code;

    /**
     * @param string $identifier Permanent identifier for a user within the white label application.
     * @param string $qrCodeUrl URL for a QR code image to be used by the white label mobile application that will be
     * used to automatically pair a device with the white label user.
     * @param string $code Code to to be used by the white label mobile application that will be used to manually pair a
     * device with the white label user.
     */
    public function __construct($identifier, $qrCodeUrl, $code)
    {
        $this->identifier = $identifier;
        $this->qrCodeUrl = $qrCodeUrl;
        $this->code = $code;
    }

    /**
     * Get the permanent identifier for a user within the white label application.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Get the URL for a QR code image to be used by the white label mobile application that will be
     * used to automatically pair a device with the white label user.
     *
     * @return string
     */
    public function getQrCodeUrl()
    {
        return $this->qrCodeUrl;
    }

    /**
     * Get the code to to be used by the white label mobile application that will be used to manually pair a
     * device with the white label user.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }
}
