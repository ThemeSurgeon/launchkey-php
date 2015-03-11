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
        $response = new PingResponse($launchKeyTime, null, new \DateTime());
        $this->assertSame($launchKeyTime, $response->getLaunchKeyTime());
    }

    public function testGetPublicKey()
    {
        $response = new PingResponse(new \DateTime(), "public key", new \DateTime());
        $this->assertEquals("public key", $response->getPublicKey());
    }

    public function testGetKeyTimeStamp()
    {
        $stamp = new \DateTime("-10 minutes");
        $response = new PingResponse(new \DateTime(), null, $stamp);
        $this->assertSame($stamp, $response->getKeyTimeStamp());
    }

    public function testPingResponseToJSONReturnsProperJSONString()
    {
        $launchKeyTime = new \DateTime("-10 minutes");
        $publicKey = "Public Key";
        $keyTimeStamp = new \DateTime("-20 minutes");

        $expected = json_encode(array(
            "launchKeyTime" => $launchKeyTime->getTimestamp() * 1000,
            "publicKey" => $publicKey,
            "keyTimeStamp" => $keyTimeStamp->getTimestamp() * 1000
        ));

        $pingResponse = new PingResponse($launchKeyTime, $publicKey, $keyTimeStamp);
        $actual = $pingResponse->toJson();
        $this->assertEquals($expected, $actual);
    }

    public function testPingResponseFromJson()
    {
        $launchKeyTime = new \DateTime("-10 minutes");
        $publicKey = "Public Key";
        $keyTimeStamp = new \DateTime("-20 minutes");

        $expected = new PingResponse($launchKeyTime, $publicKey, $keyTimeStamp);
        $actual = PingResponse::fromJson(json_encode(array(
            "launchKeyTime" => $launchKeyTime->getTimestamp() * 1000,
            "publicKey" => $publicKey,
            "keyTimeStamp" => $keyTimeStamp->getTimestamp() * 1000
        )));

        $this->assertEquals($expected, $actual);
    }

    public function testPingResponseFromJsonThrowsInvalidArgumentExceptionOnInvalidJson()
    {
        $this->setExpectedException('\InvalidArgumentException');
        PingResponse::fromJson("Not valid JSON");
    }

    public function testPingResponseFromJsonThrowsInvalidArgumentExceptionOnNoLaunchKeyTime()
    {
        $this->setExpectedException('\InvalidArgumentException');
        PingResponse::fromJson(json_encode(array(
            "publicKey" => "public key",
            "keyTimeStamp" => 1001
        )));
    }

    public function testPingResponseFromJsonThrowsInvalidArgumentExceptionOnNoPublicKey()
    {
        $this->setExpectedException('\InvalidArgumentException');
        PingResponse::fromJson(json_encode(array(
            "launchKeyTime" => 1000,
            "keyTimeStamp" => 1001
        )));
    }

    public function testPingResponseFromJsonThrowsInvalidArgumentExceptionOnNoKeyTimeStamp()
    {
        $this->setExpectedException('\InvalidArgumentException');
        PingResponse::fromJson(json_encode(array(
            "launchKeyTime" => 1000,
            "publicKey" => "public key"
        )));
    }

    public function testPingResponseFromJsonThrowsInvalidArgumentExceptionOnNonNumericKeyTimeStamp()
    {
        $this->setExpectedException('\InvalidArgumentException');
        PingResponse::fromJson(json_encode(array(
            "launchKeyTime" => 1000,
            "publicKey" => "public key",
            "keyTimeStamp" => "non-numeric"
        )));
    }

    public function testPingResponseFromJsonThrowsInvalidArgumentExceptionOnNonNumericLaunchKeyTime()
    {
        $this->setExpectedException('\InvalidArgumentException');
        PingResponse::fromJson(json_encode(array(
            "launchKeyTime" => "non-numeric",
            "publicKey" => "public key",
            "keyTimeStamp" => 1000
        )));
    }
}
