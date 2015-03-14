<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

namespace LaunchKey\SDK\Guzzle;


class RequestFactory extends \Guzzle\Http\Message\RequestFactory
{
    /**
     * Override so we can always have a body
     * @var string
     */
    protected $requestClass = 'Guzzle\Http\Message\EntityEnclosingRequest';

    /** @var RequestFactory Singleton instance of the default request factory */
    protected static $instance;
}
