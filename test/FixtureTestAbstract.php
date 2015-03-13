<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Test;


class FixtureTestAbstract extends \PHPUnit_Framework_TestCase
{
    protected static $fixtures = array();

    protected function getFixture($filename)
    {

        if (!isset(self::$fixtures[$filename])) {
            self::$fixtures[$filename] = file_get_contents(sprintf("%s/__fixtures/%s", __DIR__, $filename));
        }
        return self::$fixtures[$filename];
    }
}
