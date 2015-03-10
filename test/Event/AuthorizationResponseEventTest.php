<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Event;

use LaunchKey\SDK\Event\AuthorizationResponseEvent;

class AuthorizationResponseEventTest extends \PHPUnit_Framework_TestCase
{

    public function testGetAuthorizationResponse()
    {
        $response = \Phake::mock('\LaunchKey\SDK\Domain\AuthorizationResponse');
        $event = new AuthorizationResponseEvent($response);
        $this->assertSame($response, $event->getAuthorizationResponse());
    }
}
