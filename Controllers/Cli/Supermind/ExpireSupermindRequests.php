<?php

namespace Minds\Controllers\Cli\Supermind;

use Minds\Core\Supermind\Manager as SupermindManager;

/**
 * Cli script to mark Supermind requests as expired after 7 days from request creation
 */
class ExpireSupermindRequests extends \Minds\Cli\Controller implements \Minds\Interfaces\CliControllerInterface
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
        $supermindManager = $this->getSupermindManager();
        $this->out("About to update expired Supermind requests");
        $supermindManager->expireRequests();
        $this->out("Updated expired Supermind requests");
    }

    private function getSupermindManager(): SupermindManager
    {
        return new SupermindManager();
    }
}
