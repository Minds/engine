<?php

namespace Minds\Core\Queue\Runners;

use Minds\Core\Queue\Interfaces;
use Minds\Core\Queue;
use Minds\Core\Events\Dispatcher;

/**
 * User State queue runner.
 */
class UserStateChange implements Interfaces\QueueRunner
{
    public function run()
    {
        $client = Queue\Client::Build();
        $client->setQueue('UserStateChanges')
            ->receive(function ($data) {
                   $data = $data->getData();
                $event = isset($data['estimate']) ? 'user_state_change_estimate' : 'user_state_change';
                Dispatcher::trigger($event, $data['user_state_change']['state'], $data['user_state_change']);
               });
    }
}
