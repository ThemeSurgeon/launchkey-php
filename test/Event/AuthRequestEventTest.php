<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Event;


use LaunchKey\SDK\Domain\AuthRequest;
use LaunchKey\SDK\Event\AuthRequestEvent;

class AuthRequestEventTest extends \PHPUnit_Framework_TestCase
{

    public function testGetAuthorizationRequest()
    {
        $request = \Phake::mock('\LaunchKey\SDK\Domain\AuthRequest');
        $event = new AuthRequestEvent($request);
        $this->assertSame($request, $event->getAuthRequest());
    }
}
