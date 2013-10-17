<?php
class LaunchKey_ClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * This is dumb and should be removed if more tests are added, but it's
     * here because autoloading is what I was fixing.
     */
    public function testAutloadWorks()
    {
        $client = new LaunchKey_Client(null, null, null, null);
    }
}
