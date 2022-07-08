<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Interfaces;
use Minds\Core\Blockchain\Skale\Keys as SkaleKeys;
use Minds\Core\Blockchain\Skale\Transaction\Manager as TransactionManager;
use Minds\Core\Blockchain\Wallets\Skale\Balance;

/**
 * SKALE CLI - check balance and interact with custodial wallets via CLI.
 */
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

    // TODO: Core/Blockchain/SkaleTools

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
     * Gets token balance for a given address or username, without cache.
     * If no address is manually passed in, will get balance for a users custodial wallet.
     * @example php cli.php Skale getTokenBalance --username=testuser
     * @return void
     */
    public function getTokenBalance(): void
    {
        $address = $this->getWalletAddress(); // parses --username and --address opts.

        if (!$address) {
            $this->out('You must provide a username or address');
            return;
        }

        $balance = (new Balance())->getTokenBalance(
            address: $address,
            useCache: false
        );

        $this->out("address:\t" . $address);
        $this->out("balance:\t" . $balance . " wei");
    }

    /**
     * Gets sFuel balance (equivalent of Ether on the network) for a given address
     * or username, without cache. If no address is manually passed in, will get balance
     * for a users custodial wallet.
     * @example php cli.php Skale getSFuelBalance --username=testuser
     * @return void
     */
    public function getSFuelBalance(): void
    {
        $address = $this->getWalletAddress(); // parses --username and --address opts.

        if (!$address) {
            $this->out('You must provide a username or address');
            return;
        }

        $balance = (new Balance())->getSFuelBalance(
            address: $address,
            useCache: false
        );

        $this->out("address:\t" . $address);
        $this->out("balance:\t" . $balance . " wei");
    }

    /**
     * Send sFuel (SKALE network Ether) from a custodial wallet to either
     * another custodial wallet, or an external Ethereum address.
     * @example
     * - php cli.php Skale sendSFuel --senderUsername=minds --receiverAddress=0x00000...
     * - php cli.php Skale sendSFuel --senderUsername=minds --receiverUsername=testuser
     * @return void
     */
    public function sendSFuel(): void
    {
        // get opts.
        $senderUsername = $this->getOpt('senderUsername') ?? false;
        $receiverUsername = $this->getOpt('receiverUsername') ?? false;
        $receiverAddress = $this->getOpt('receiverAddress') ?? false;

        // validate required opts were passed in.
        if (!$senderUsername && (!$receiverAddress || !$receiverUsername)) {
            $this->out('You must set a sender and either a receiverAddress or receiverUsername');
            return;
        }

        // build sender and if no receiverAddress was passed in, receiver too.
        /** @var EntitiesBuilder */
        $entitiesBuilder = Di::_()->get('EntitiesBuilder');
        $sender = $entitiesBuilder->getByUserByIndex($senderUsername);
        $receiver = null;

        if (!$receiverAddress) {
            $receiver = $entitiesBuilder->getByUserByIndex($receiverUsername);
        }

        if (!$sender || (!isset($receiver) && !$receiver && !$receiverAddress)) {
            $this->out('Unable to construct both sender and receiver, or missing receiver address');
            return;
        }
    
        // prepare and send transaction.
        /** @var TransactionManager */
        $transactionManager = new TransactionManager();
        $txHash = null;
        if ($receiverAddress) {
            $txHash = $transactionManager->withUsers(
                sender: $sender,
                receiverAddress: $receiverAddress
            )->sendSFuel();
        } else {
            $txHash = $transactionManager->withUsers(
                sender: $sender,
                receiver: $receiver
            )->sendSFuel();
        }

        $this->out('Sent with tx hash '. $txHash);
    }
    

    /**
     * Send SKALE MINDS from a custodial wallet to either
     * another custodial wallet, or an external Ethereum address.
     * @example
     * Examples to send 0.01 tokens
     * - php cli.php Skale sendTokens --senderUsername=minds --receiverUsername=testuser --amountWei=10000000000000000
     * - php cli.php Skale sendTokens --senderUsername=testuser --receiverAddress=0x00000... --amountWei=10000000000000000
     * @return void
     */
    public function sendTokens()
    {
        // get opts.
        $senderUsername = $this->getOpt('senderUsername') ?? false;
        $receiverUsername = $this->getOpt('receiverUsername') ?? false;
        $receiverAddress = $this->getOpt('receiverAddress') ?? false;
        $amountWei = $this->getOpt('amountWei') ?? false;
        // validate required opts were passed in.
        if (!$senderUsername || !$amountWei || (!$receiverAddress && !$receiverUsername)) {
            $this->out('You must set a sender, amount and either a receiverAddress or receiverUsername');
            return;
        }

        // build sender and if no receiverAddress was passed in, receiver too.
        /** @var EntitiesBuilder */
        $entitiesBuilder = Di::_()->get('EntitiesBuilder');
        $sender = $entitiesBuilder->getByUserByIndex($senderUsername);
        $receiver = null;

        if (!$receiverAddress) {
            $receiver = $entitiesBuilder->getByUserByIndex($receiverUsername);
        }

        if (!$sender || (!isset($receiver) && !$receiver && !$receiverAddress)) {
            $this->out('Unable to construct both sender and receiver, or missing receiver address');
            return;
        }
    
        // prepare and send transaction.
        /** @var TransactionManager */
        $transactionManager = new TransactionManager();
        $txHash = null;
        if ($receiverAddress) {
            $txHash = $transactionManager->withUsers(
                sender: $sender,
                receiverAddress: $receiverAddress
            )->sendTokens($amountWei);
        } else {
            $txHash = $transactionManager->withUsers(
                sender: $sender,
                receiver: $receiver
            )->sendTokens($amountWei);
        }
        $this->out('Sent with tx hash '. $txHash);
    }

    /**
     * Parses opts to get wallet address - if a username is passed in
     * hydrates user and gets custodial SKALE wallet address.
     * To check a non-custodial wallet, pass an address.
     * @return string|null - wallet address or null if there is no address.
     */
    private function getWalletAddress(): ?string
    {
        $address = $this->getOpt('address');
        
        if ($address) {
            return $address;
        }

        $username = $this->getOpt('username');

        if (!$username && !$address) {
            return null;
        }

        if (!$address && $username) {
            return $this->getCustodialWalletAddressByUsername($username);
        }

        return null;
    }

    /**
     * Gets custodial SKALE wallet address by username.
     * @param string|null $username - username to get wallet address for.
     * @return string|null - SKALE wallet address for a user.
     */
    private function getCustodialWalletAddressByUsername(string $username): ?string
    {
        $user = Di::_()->get('EntitiesBuilder')->getByUserByIndex($username);
        $keys = (new SkaleKeys())->withUser($user);
        return $keys->getWalletAddress();
    }
}
