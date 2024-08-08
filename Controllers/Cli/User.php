<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Cli;
use Minds\Interfaces;
use Minds\Exceptions;
use Minds\Entities;
use Minds\Helpers;
use Minds\Core\Channels\Ban;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Exceptions\CliException;
use PDO;
use Selective\Database\Connection;
use Selective\Database\Operator;
use Selective\Database\RawExp;

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

            (new Save())->setEntity($user)->withMutatedAttributes(['password', 'password_reset_code'])->save();

            $this->out("Password changed successfuly for user ".$username);
        } catch (\Exception $e) {
            $this->out("An error has occured");
            $this->out($e);
        }
    }

    public function change_email()
    {
        try {
            if (!$this->getOpt('username') || !$this->getOpt('email')) {
                throw new Exceptions\CliException('Missing username / email');
            }

            $username = $this->getOpt('username');
            $email = $this->getOpt('email');

            $user = new Entities\User($username);
        
            $user->email = $email;

            (new Save())->setEntity($user)->withMutatedAttributes(['email'])->save();

            $this->out("Email changed successfuly for user ".$username);
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
     * `php cli.php User baneFromCsv --csv=bans.svc --reason=16`
     */
    public function banFromCsv()
    {
        $filename = $this->getOpt('csv');
        $row = 1;
        if (($handle = fopen($filename, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $guid = $data[0];
                $user = Di::_()->get('EntitiesBuilder')->single($guid);
                if (!$user instanceof Entities\User) {
                    continue;
                }

                $ban = new Ban();
                $ban->setUser($user);
                $this->out("Banning ".$user->getGuid()."...");
                $ban->ban($this->getOpt('reason') ?? 1);
            }
            fclose($handle);
        }
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

    public function remap_emails()
    {
        global $CONFIG;
        $mysqlClient = Di::_()->get(Core\Data\MySQL\Client::class);
        $mysqlClientReader = $mysqlClient->getConnection(Core\Data\MySQL\Client::CONNECTION_REPLICA);
        $mysqlClientReaderHandler = new Connection($mysqlClientReader);
        $mysqlClientWriter = $mysqlClient->getConnection(Core\Data\MySQL\Client::CONNECTION_MASTER);
        $mysqlClientWriterHandler = new Connection($mysqlClientWriter);

        $stmt = $mysqlClientReaderHandler->select()
            ->columns([
                'tenant_id',
                'email'
            ])
            ->from('minds_entities_user')
            ->prepare();

        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $encryptedEmail = $row['email'];
            $email = Helpers\OpenSSL::decrypt(base64_decode($encryptedEmail, true), file_get_contents($CONFIG->encryptionKeys['email']['private']));
            $this->out($email);
        
            if ($email) {
                $updateStmt = $mysqlClientWriterHandler->update()
                    ->table('minds_entities_user')
                    ->set([
                        'email' => new RawExp(':email')
                    ])
                    ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
                    ->where('email', Operator::EQ, new RawExp(':encrypted_email'))
                    ->prepare();
                $updateStmt->execute([
                    'encrypted_email' => $encryptedEmail,
                    'email' => $email,
                    'tenant_id' => $row['tenant_id'],
                ]);
            }
        }

    }

    public function delete(): void
    {
        $userGuid = $this->getOpt('guid');
        $tenantId = $this->getOpt('tenantId');

        if (!$userGuid) {
            throw new CliException('Missing guid');
        }

        if ($tenantId) {
            Di::_()->get(MultiTenantBootService::class)->bootFromTenantId($tenantId);
        }

        Di::_()->get('Queue')
            ->setQueue('ChannelDeferredOps')
            ->send([
                'type' => 'delete',
                'user_guid' => $userGuid
            ]);
    }
}
