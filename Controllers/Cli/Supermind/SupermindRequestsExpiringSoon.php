<?php

namespace Minds\Controllers\Cli\Supermind;

use Minds\Core\Supermind\Manager as SupermindManager;

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
        //ojm cli file
        $supermindManager = $this->getSupermindManager();
        $this->out("About to trigger events for expiring Supermind requests");

        $supermindManager->triggerExpiringSoonEvents();
        $this->out("Supermind requests expiring soon events triggered");
    }

    private function getSupermindManager(): SupermindManager
    {
        return new SupermindManager();
    }
}
