<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Guzzle;


use LaunchKey\SDK\Guzzle\RequestFactory;

class RequestFactoryTest extends \PHPUnit_Framework_TestCase
{

    public function testGetInstanceReturnsLaunchKeyVersion()
    {
        $factory = RequestFactory::getInstance();
        $this->assertInstanceOf('\LaunchKey\SDK\Guzzle\RequestFactory', $factory);
        return $factory;
    }

    /**
     * @depends testGetInstanceReturnsLaunchKeyVersion
     */
    public function testCreateGetReturnsEntityEnclosingRequest(RequestFactory $factory)
    {
        $this->assertInstanceOf('Guzzle\\Http\\Message\\EntityEnclosingRequest', $factory->create("GET", "/"));
    }
}
