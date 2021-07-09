<?php

namespace Minds\Core\Queue\Runners;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Queue\Interfaces;
use Minds\Entities\Video;

class OAuthEvents implements Interfaces\QueueRunner
{
    /** @var EventsDispatcher */
    protected $eventsDispatcher;

    public function __construct(EventsDispatcher $eventsDispatcher = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?? Di::_()->get('EventsDispatcher');
    }

    public function run()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $client = Core\Queue\Client::Build();
        $client->setQueue("OAuthEvents")
            ->receive(function ($data) {
                $d = $data->getData();
                $this->eventsDispatcher->trigger('OAuth\Background', $d['event'], $d);
            }, ['max_messages' => 1]);
    }
}
