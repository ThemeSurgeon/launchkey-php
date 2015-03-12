<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Event;


use LaunchKey\SDK\Domain\WhiteLabelUser;
use LaunchKey\SDK\Event\WhiteLabelUserCreatedEvent;

class WhiteLabelUserCreatedEventTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @Mock
     * @var WhiteLabelUser
     */
    private $whiteLabelUser;

    public function testGetWhiteLabelUser()
    {
        $event = new WhiteLabelUserCreatedEvent($this->whiteLabelUser);
        $this->assertSame($this->whiteLabelUser, $event->getWhiteLabelUser());
    }

    protected function setUp()
    {
        \Phake::initAnnotations($this);
    }

    protected function tearDown()
    {
        $this->whiteLabelUser = null;
    }
}
