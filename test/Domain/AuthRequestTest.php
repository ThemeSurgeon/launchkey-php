<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Domain;


use LaunchKey\SDK\Domain\AuthRequest;

class AuthRequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AuthRequest
     */
    private $authRequest;

    public function testUsername()
    {
        $this->assertEquals("username", $this->authRequest->getUsername());
    }

    public function testIsUserSession()
    {
        $this->assertTrue($this->authRequest->isUserSession());
    }

    public function testAuthorizationRequestId()
    {
        $this->assertEquals("auth request", $this->authRequest->getAuthRequestId());
    }

    protected function setUp()
    {
        $this->authRequest = new AuthRequest("username", true, "auth request");
    }

    protected function tearDown()
    {
        $this->authRequest = null;
    }
}
