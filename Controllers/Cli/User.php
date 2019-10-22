<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Cli;
use Minds\Interfaces;
use Minds\Exceptions;
use Minds\Entities;
use Minds\Core\Channels\Manager;
use Minds\Core\Di\Di;

class User extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }
    
    public function exec()
    {
        $this->out('Missing subcommand');
    }

    public function set_feature_flags()
    {
        if (!$this->args) {
            throw new Exceptions\CliException('Missing users');
        }

        $username = array_shift($this->args);
        $features = $this->args;

        $user = new Entities\User($username);

        if (!$user || !$user->guid) {
            throw new Exceptions\CliException('User not found');
        }

        // TODO: Logout all sessions

        $user->setFeatureFlags($features);
        $user->save();

        if (!$features) {
            $this->out("Removed all feature flags for {$user->username}");
        } else {
            $this->out("Set feature flags for {$user->username}: " . implode(', ', $features));
        }
    }

    /**
     * Resets a users passwords.
     * Requires username and password.
     *
     * Example call: php ./cli.php User password_reset --username=nemofin --password=password123
     * @return void
     */
    public function password_reset()
    {
        try {
            if (!$this->getOpt('username') || !$this->getOpt('password')) {
                throw new Exceptions\CliException('Missing username / password');
            }

            $username = $this->getOpt('username');
            $password = $this->getOpt('password');

            $user = new Entities\User($username);
        
            $user->password = Core\Security\Password::generate($user, $password);
            $user->password_reset_code = "";
            $user->override_password = true;
            $user->save();

            $this->out("Password changed successfuly for user ".$username);
        } catch (Exception $e) {
            $this->out("An error has occured");
            $this->out($e);
        }
    }

    //Updates an user's activities based on their current state
    public function reindex() : void
    {
        $username = $this->getOpt('username');
        $user = new Entities\User($username);
        if ($user === null || $user->getType() !== 'user') {
            $this->out("Cannot find user to reindex");
        }
        $this->out("Reindexing {$username}");
        /** @var Manager $channelsManager */
        $channelsManager = Di::_()->get('Channels\Manager');
        $channelsManager->setUser($user);
        $channelsManager->updateOwnerObject();
    }
}
