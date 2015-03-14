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

    /**
     * @var AuthResponse
     */
    private $defaultEntity;

    protected function setUp()
    {
        $this->defaultEntity = new AuthResponse();
        $this->entity = new AuthResponse(true, "AuthResponseID", "UserHash", "OrganizationUserID", "UserPushID", "DeviceID", true);
    }

    protected function tearDown()
    {
        $this->entity = null;
        $this->defaultEntity = null;
    }

    public function testAuthRequestId()
    {
        $this->assertEquals("AuthResponseID", $this->entity->getAuthRequestId());
    }

    public function testAuthRequestIdDefaultsToNull()
    {
        $this->assertNull($this->defaultEntity->getAuthRequestId());
    }

    public function testUserHash()
    {
        $this->assertEquals("UserHash", $this->entity->getUserHash());
    }

    public function testUserHashDefaultsToNull()
    {
        $this->assertNull($this->defaultEntity->getUserHash());
    }

    public function testIsCompleted()
    {
        $this->assertTrue($this->entity->isCompleted());
    }

    public function testIsCompletedDefaultsToFalse()
    {
        $this->assertFalse($this->defaultEntity->isCompleted());
    }

    public function testOrganizationUserIdDefaultsToNull()
    {
        $this->assertNull($this->defaultEntity->getOrganizationUserId());
    }

    public function testOrganizationUserId()
    {
        $this->assertEquals("OrganizationUserID", $this->entity->getOrganizationUserId());
    }

    public function testUserPushIdDefaultsToNull()
    {
        $this->assertNull($this->defaultEntity->getUserHash());
    }

    public function testUserPushId()
    {
        $this->assertEquals("UserPushID", $this->entity->getUserPushId());
    }

    public function testIsAuthorizedDefaultsToNull()
    {
        $this->assertNull($this->defaultEntity->isAuthorized());
    }

    public function testIsAuthorized()
    {
        $this->assertTrue($this->entity->isAuthorized());
    }

    public function testDeviceIdDefaultsToNull()
    {
        $this->assertNull($this->defaultEntity->getDeviceId());
    }

    public function testDeviceId()
    {
        $this->assertEquals("DeviceID", $this->entity->getDeviceId());
    }
}
