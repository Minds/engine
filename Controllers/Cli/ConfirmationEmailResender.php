<?php

/**
 * ConfirmationEmailResender CLI
 *
 * @author eiennohi
 */

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\Email\Confirmation\Manager;
use Minds\Interfaces;

class ConfirmationEmailResender extends Cli\Controller implements Interfaces\CliControllerInterface
{
    /**
     * Echoes $commands (or overall) help text to standard output.
     * @param string|null $command - the command to be executed. If null, it corresponds to exec()
     * @return null
     */
    public function help($command = null)
    {
        $this->out('Usage: cli ConfirmationEmailResender');
    }

    /**
     * Executes the default command for the controller.
     * @return mixed
     */
    public function exec()
    {
        \Minds\Core\Events\Defaults::_();

        /** @var Manager $manager */
        $manager = Di::_()->get('Email\Confirmation');

        $users = $manager->fetchNewUnverifiedUsers();

        if (!isset($users) || count($users) === 0) {
            $this->out("[ConfirmationEmailResender]: No newly registered users found");
            return;
        }

        foreach ($users as $user) {
            try {
                $manager
                    ->setUser($user)
                    ->sendEmail();

                $this->out("[ConfirmationEmailResender]: Email sent to {$user->guid}");
            } catch (\Exception $e) {
                $this->out("[ConfirmationEmailResender]: {$e->getMessage()}");
            }
        }
    }
}
