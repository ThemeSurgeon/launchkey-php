<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test\Domain;


use LaunchKey\SDK\Domain\DeOrbitRequest;

class DeOrbitRequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DeOrbitRequest
     */
    private $object;

    public function testRequestId()
    {
        $this->assertEquals("AuthRequest", $this->object->getAuthRequestId());
    }

    protected function setUp()
    {
        $this->object = new DeOrbitRequest("AuthRequest");
    }

    protected function tearDown()
    {
        $this->object = null;
    }
}
