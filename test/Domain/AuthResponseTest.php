<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Domain;


use LaunchKey\SDK\Domain\AuthResponse;

class AuthResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AuthResponse
     */
    private $entity;

    protected function setUp()
    {
        $this->entity = new AuthResponse("ID", "UserHash", "OrganizationUserID", "UserPushID");
    }

    protected function tearDown()
    {
        $this->entity = null;
    }

    public function testId()
    {
        $this->assertEquals("ID", $this->entity->getAuthRequestId());
    }

    public function testUserHash()
    {
        $this->assertEquals("UserHash", $this->entity->getUserHash());
    }

    public function testIsCompletedDefaultsToFalse()
    {
        $this->assertFalse($this->entity->isCompleted());
    }

    public function testIsCompleted()
    {
        $entity = new AuthResponse(null, null, null, null, true);
        $this->assertTrue($entity->isCompleted());
    }

    public function testOrganizationUserId()
    {
        $this->assertEquals("OrganizationUserID", $this->entity->getOrganizationUserId());
    }

    public function testUserPushId()
    {
        $this->assertEquals("UserPushID", $this->entity->getUserPushId());
    }

    public function testIsAuthorizedDefaultsToNull()
    {
        $this->assertNull($this->entity->isAuthorized());
    }

    public function testIsAuthorized()
    {
        $entity = new AuthResponse(null, null, null, null, null, true);
        $this->assertTrue($entity->isAuthorized());
    }
}
