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
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param CryptService $cryptService
     * @param ApiService $httpService
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(CryptService $cryptService, ApiService $httpService, EventDispatcher $eventDispatcher)
    {
        $this->cryptService = $cryptService;
        $this->httpService = $httpService;
        $this->eventDispatcher = $eventDispatcher;
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

    public function handleCallback(array $postData)
    {
        // TODO: Implement handleCallback() method.
    }
}
