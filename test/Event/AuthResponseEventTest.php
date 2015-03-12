<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Event;

use LaunchKey\SDK\Event\AuthResponseEvent;

class AuthResponseEventTest extends \PHPUnit_Framework_TestCase
{

    public function testGetAuthorizationResponse()
    {
        $response = \Phake::mock('\LaunchKey\SDK\Domain\AuthResponse');
        $event = new AuthResponseEvent($response);
        $this->assertSame($response, $event->getAuthResponse());
    }
}
