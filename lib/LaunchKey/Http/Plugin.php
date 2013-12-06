<?php

namespace LaunchKey\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
abstract class Plugin implements EventSubscriberInterface {

    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send' => 'call',
            'request.sent'        => 'finalize',
        );
    }

    protected $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function call($event)
    {
        return;
    }

    public function finalize($event)
    {
        return;
    }

} // End Plugin
