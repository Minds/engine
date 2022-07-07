<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Interfaces;
use Minds\Core\Blockchain\Skale\Keys as SkaleKeys;
use Minds\Core\Blockchain\Wallets\Skale\Minds\Balance;

class Skale extends Cli\Controller implements Interfaces\CliControllerInterface
{
    /**
     * Outputs basic help info
     * @param $command - unused.
     * @return void
     */
    public function help($command = null)
    {
        $this->out('Usage: cli Skale ...');
    }

    /**
     * Default executing method - not implemented
     * So outputs help information.
     * @return void
     */
    public function exec()
    {
        $this->help();
    }

    /**
     * Generate keys for a given username.
     * @example php cli.php Skale generateKeys --username=testuser
     * @return void
     */
    public function generateKeys(): void
    {
        $username = $this->getOpt('username');
        Di::_()->get('Nostr\PocSync')->syncChannel($username);
    }

    /**
     * Prints public key and address for a given username. If the skale development mode
     * is enabled, will also print private key.
     * @example php cli.php Skale printKeys --username=testuser
     * @return void
     */
    public function printKeys(): void
    {
        $username = $this->getOpt('username');

        if (!$username) {
            $this->out('You must provide a username');
            return;
        }

        $user = Di::_()->get('EntitiesBuilder')->getByUserByIndex($username);
        $keys = (new SkaleKeys())->withUser($user);

        if (Di::_()->get('Config')->get('blockchain')['skale']['development_mode'] ?? false) {
            $this->out("private key:\t". bin2hex($keys->getSecp256k1PrivateKey()));
        }

        $this->out("public key:\t" . $keys->getSecp256k1PublicKey());
        $this->out("eth address:\t" . $keys->getWalletAddress());
    }

    /**
     * Gets balance for a given address or username, without cache.
     * @example php cli.php Skale getBalance --username=testuser
     * @return void
     */
    public function getBalance(): void
    {
        $username = $this->getOpt('username');
        $address = $this->getOpt('address');

        if (!$username && !$address) {
            $this->out('You must provide a username or address');
            return;
        }

        if ($username) {
            $user = Di::_()->get('EntitiesBuilder')->getByUserByIndex($username);
            $keys = (new SkaleKeys())->withUser($user);
            $address = $keys->getWalletAddress();
        }

        $balance = (new Balance())->get(
            address: $address,
            useCache: false
        );

        $this->out("address:\t" . $address);
        $this->out("balance:\t" . $balance . " wei");
    }

    // Should be tied into token sending in future.
    // public function sendSfuel() {}

    // Should take wallet addresses or user guids.
    // public function sendTokens() {}
}
