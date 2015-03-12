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
    private $authorizationRequest;

    public function testUsername()
    {
        $this->assertEquals("username", $this->authorizationRequest->getUsername());
    }

    public function testIsUserSessionDefaultsToFalse()
    {
        $this->assertFalse($this->authorizationRequest->isUserSession());
    }

    public function testIsUserSession()
    {
        $authorizationRequest = new AuthRequest("username", true);
        $this->assertTrue($authorizationRequest->isUserSession());
    }

    protected function setUp()
    {
        $this->authorizationRequest = new AuthRequest("username");
    }

    protected function tearDown()
    {
        $this->authorizationRequest = null;
    }
}
