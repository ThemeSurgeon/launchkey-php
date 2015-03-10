<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Event;


use LaunchKey\SDK\Domain\AuthorizationRequest;
use LaunchKey\SDK\Event\AuthorizationRequestEvent;

class AuthorizationRequestEventTest extends \PHPUnit_Framework_TestCase
{

    public function testGetAuthorizationRequest()
    {
        $request = \Phake::mock('\LaunchKey\SDK\Domain\AuthorizationRequest');
        $event = new AuthorizationRequestEvent($request);
        $this->assertSame($request, $event->getAuthorizationRequest());
    }
}
