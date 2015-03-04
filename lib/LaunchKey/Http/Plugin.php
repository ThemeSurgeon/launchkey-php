<?php

namespace LaunchKey\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
abstract class Plugin implements EventSubscriberInterface
{

    protected $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send' => 'call',
            'request.sent' => 'finalize',
        );
    }

    public function call($event)
    {
        return;
    }

    public function finalize($event)
    {
        return;
    }
}
