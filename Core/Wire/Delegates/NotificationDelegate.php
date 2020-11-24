<?php

namespace Minds\Core\Wire\Delegates;

use Minds\Core\Wire\Wire;
use Minds\Core\Queue;

class NotificationDelegate
{
    /** @var Queue\Client $queue */
    protected $queue;

    public function __construct($queue = null)
    {
        $this->queue = $queue ?: Queue\Client::build();
    }

    /**
     * OnAdd, send a notification
     * @param Wire $wire
     * @return void
     */
    public function onAdd(Wire $wire) : void
    {
        if ($wire->getTrialDays()) {
            return; // Do not send notification if trial
        }
        $this->queue->setQueue('WireNotification')
            ->send(
                [
                    'wire' => serialize($wire),
                    'entity' => serialize($wire->getEntity()),
                ]
            );
    }
}
