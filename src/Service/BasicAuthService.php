<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Service;


class BasicAuthService implements AuthService
{
    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * @var ApiService
     */
    private $httpService;

    /**
     * @param CryptService $cryptService
     * @param ApiService $httpService
     */
    public function __construct(CryptService $cryptService, ApiService $httpService)
    {
        $this->cryptService = $cryptService;
        $this->httpService = $httpService;
    }

    public function authorize($username) {

    }

    public function authenticate($username) {

    }

    public function isAuthorized($username)
    {
        // TODO: Implement isAuthorized() method.
    }

    public function deOrbit($authRequestId)
    {
        // TODO: Implement deOrbit() method.
    }

    public function handleCallback(array $formData)
    {
        // TODO: Implement handleCallback() method.
    }
}
