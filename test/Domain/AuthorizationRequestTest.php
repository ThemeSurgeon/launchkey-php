<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Domain;


use LaunchKey\SDK\Domain\AuthorizationRequest;

class AuthorizationRequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AuthorizationRequest
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
        $authorizationRequest = new AuthorizationRequest("username", true);
        $this->assertTrue($authorizationRequest->isUserSession());
    }

    protected function setUp()
    {
        $this->authorizationRequest = new AuthorizationRequest("username");
    }

    protected function tearDown()
    {
        $this->authorizationRequest = null;
    }
}
