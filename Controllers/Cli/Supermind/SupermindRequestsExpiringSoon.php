<?php

namespace Minds\Controllers\Cli\Supermind;

use Minds\Core\Supermind\ExpiringSoonEvents;

/**
 * Cli script to get all supermind requests that will be expiring soon
 * So we can notify recipients that they need to reply so they can get paid
 */
class SupermindRequestsExpiringSoon extends \Minds\Cli\Controller implements \Minds\Interfaces\CliControllerInterface
{
    /**
     * @inheritDoc
     */
    public function help($command = null)
    {
        $this->out("TBD");
    }

    /**
     * @inheritDoc
     */
    public function exec()
    {
        $expiringSoonEvents = new ExpiringSoonEvents();
        $this->out("About to trigger events for Supermind requests expiring soon");

        $expiringSoonEvents->triggerExpiringSoonEvents();
        $this->out("Supermind requests expiring soon events triggered");
    }
}
