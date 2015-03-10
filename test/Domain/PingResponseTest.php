<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Domain;


use LaunchKey\SDK\Domain\PingResponse;

class PingResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testGetLaunchKeyTime()
    {
        $launchKeyTime = new \DateTime("-10 minutes");
        $response = new PingResponse($launchKeyTime, null);
        $this->assertSame($launchKeyTime, $response->getLaunchKeyTime());
    }

    public function testGetPublicKey()
    {
        $response = new PingResponse(new \DateTime(), "public key");
        $this->assertEquals("public key", $response->getPublicKey());
    }
}
