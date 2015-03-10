<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Domain;


use LaunchKey\SDK\Domain\AuthorizationResponse;

class AuthorizationResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AuthorizationResponse
     */
    private $entity;

    protected function setUp()
    {
        $this->entity = new AuthorizationResponse("ID", "UserHash", "OrganizationUserID", "UserPushID");
    }

    protected function tearDown()
    {
        $this->entity = null;
    }

    public function testId()
    {
        $this->assertEquals("ID", $this->entity->getId());
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
        $entity = new AuthorizationResponse(null, null, null, null, true);
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
        $entity = new AuthorizationResponse(null, null, null, null, null, true);
        $this->assertTrue($entity->isAuthorized());
    }
}
