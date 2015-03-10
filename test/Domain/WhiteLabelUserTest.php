<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Domain;


use LaunchKey\SDK\Domain\WhiteLabelUser;

class WhiteLabelUserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WhiteLabelUser
     */
    private $whiteLabelUser;

    public function testGetIdentifier()
    {
        $this->assertEquals("identifier", $this->whiteLabelUser->getIdentifier());
    }

    public function testGetQrCodeUrl()
    {
        $this->assertEquals("qrCodeUrl", $this->whiteLabelUser->getQrCodeUrl());
    }

    public function testGetCode()
    {
        $this->assertEquals("code", $this->whiteLabelUser->getCode());
    }

    protected function setUp()
    {
        $this->whiteLabelUser = new WhiteLabelUser("identifier", "qrCodeUrl", "code");
    }

    protected function tearDown()
    {
        $this->whiteLabelUser = null;
    }

}
