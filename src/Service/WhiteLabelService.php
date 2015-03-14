<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
namespace LaunchKey\SDK\Service;

use LaunchKey\SDK\Domain\WhiteLabelUser;

interface WhiteLabelService
{
    /**
     * @param string $identifier
     * @return WhiteLabelUser
     */
    public function createUser($identifier);
}