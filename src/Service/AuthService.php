<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
namespace LaunchKey\SDK\Service;

interface AuthService
{
    public function authorize($username);

    public function authenticate($username);

    public function isAuthorized($username);

    public function deOrbit($authRequestId);

    public function handleCallback(array $formData);
}