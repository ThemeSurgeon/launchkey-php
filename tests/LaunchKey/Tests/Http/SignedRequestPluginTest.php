<?php

namespace LaunchKey\Tests\Http;

use LaunchKey\Http\SignedRequestPlugin;

/**
 * Tests SignedRequestPlugin
 *
 * @package  LaunchKey
 * @category Tests
 */
class SignedRequestPluginTest extends \PHPUnit_Framework_TestCase
{

    public function test_does_not_sign_ping_requests()
    {
        $mock_request = $this->getMockBuilder('Guzzle\\Http\\Message\\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $mock_request
            ->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue(
                '/v1/ping'
            ));

        // Oh, mocks! Y U so brittle?
        $mock_request
            ->expects($this->never())
            ->method('getMethod');

        $client = new SignedRequestPlugin(NULL);
        $client->call(array(
            'request' => $mock_request,
        ));
    }

} // End SignedRequestPluginTest
