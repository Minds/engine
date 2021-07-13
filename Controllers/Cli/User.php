<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Cli;
use Minds\Interfaces;
use Minds\Exceptions;
use Minds\Entities;
use Minds\Core\Channels\Ban;

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
        } catch (\Exception $e) {
            $this->out("An error has occured");
            $this->out($e);
        }
    }

    /**
     * Ban a user.
     * Requires username.
     * Optionally pass in reason
     * Example call: php ./cli.php User ban --username=testuser123 --reason=1
     * @return void
     */
    public function ban()
    {
        if (!$this->getOpt('username')) {
            throw new Exceptions\CliException('Missing username');
        }
        $username = $this->getOpt('username');
        $ban = new Ban();
        $user = new Entities\User($this->getOpt('username'));
        $ban->setUser($user);
        $this->out("Banning ".$username."...");
        $ban->ban($this->getOpt('reason') ?? 1);
        $this->out("Success if there are no errors above. Banned ".$username.".");
    }

    /**
     * Unban a user.
     * Requires username.
     *
     * Example call: php ./cli.php User unban --username=testuser123
     * @return void
     */
    public function unban()
    {
        if (!$this->getOpt('username')) {
            throw new Exceptions\CliException('Missing username');
        }
        $username = $this->getOpt('username');
        $ban = new Ban();
        $user = new Entities\User();
        $ban->setUser($user);
        $this->out("Unbanning ".$username);
        $ban->unban();
        $this->out("Success if there are no errors above. Unbanned ".$username.".");
    }

    public function register_complete()
    {
        $username = $this->getOpt('username');

        if (!$username) {
            throw new Exceptions\CliException('Missing username');
        }

        $user = new Entities\User(strtolower($username));

        if (!$user->guid) {
            throw new Exceptions\CliException('User does not exist');
        }

        Core\Events\Dispatcher::trigger('register/complete', 'user', [ 'user' => $user ]);
    }
}
