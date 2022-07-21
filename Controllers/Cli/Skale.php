<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Interfaces;
use Minds\Core\Blockchain\Skale\Keys as SkaleKeys;
use Minds\Core\Blockchain\Skale\Tools as SkaleTools;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Nostr\PocSync;

/**
 * SKALE CLI - check balance and interact with custodial wallets via CLI.
 */
class Skale extends Cli\Controller implements Interfaces\CliControllerInterface
{
    /**
     * Constructor.
     * @param EntitiesBuilder|null $entitiesBuilder - build entities.
     * @param PocSync|null $nostrPocSync - used to generate keys.
     * @param SkaleKeys|null $skaleKeys - used to get keys.
     * @param SkaleTools|null $skaleTools - used to get balances and send transactions.
     * @param Config|null $config - config values.
     */
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?PocSync $nostrPocSync = null,
        private ?SkaleKeys $skaleKeys = null,
        private ?SkaleTools $skaleTools = null,
        private ?Config $config = null
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->nostrPocSync ??= Di::_()->get('Nostr\PocSync');
        $this->skaleKeys ??= Di::_()->get('Blockchain\Skale\Keys');
        $this->skaleTools ??= Di::_()->get('Blockchain\Skale\Tools');
        $this->config ??= Di::_()->get('Config');
    }

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
        $this->nostrPocSync->syncChannel($username);
        $this->printKeys();
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

        $user = $this->entitiesBuilder->getByUserByIndex($username);
        $keys = $this->skaleKeys->withUser($user);

        if ($this->config->get('blockchain')['skale']['development_mode'] ?? false) {
            $this->out("private key:\t". bin2hex($keys->getSecp256k1PrivateKey()));
        }

        $this->out("public key:\t" . $keys->getSecp256k1PublicKey());
        $this->out("eth address:\t" . $keys->getWalletAddress());
    }

    /**
     * Gets token balance for a given address or username, without cache.
     * If no address is manually passed in, will get balance for a users custodial wallet.
     * @example
     * - php cli.php Skale getTokenBalance --username=testuser
     * - php cli.php Skale getTokenBalance --address=0x00000...
     * @return void
     */
    public function getTokenBalance(): void
    {
        $address = $this->getOpt('address') ?? null;
        $username = $this->getOpt('username') ?? null;

        if (!($address xor $username)) {
            $this->out('You must EITHER a username or address, but not both');
            return;
        }

        $user = null;

        if (!$address) {
            $user = $this->entitiesBuilder->getByUserByIndex($username) ?? null;
        }

        $balance = $this->skaleTools->getTokenBalance(
            address: $address ?? null,
            user: $user ?? null,
            useCache: false
        );
    
        $this->out("address:\t" . ($address ? $address : $this->skaleTools->getCustodialWalletAddress($user)));
        $this->out("balance:\t" . $balance . " wei");
    }

    /**
     * Gets sFuel balance (equivalent of Ether on the network) for a given address
     * or username, without cache. If no address is manually passed in, will get balance
     * for a users custodial wallet.
     * @example
     * - php cli.php Skale getSFuelBalance --username=testuser
     * - php cli.php Skale getSFuelBalance --address=0x00000...
     * @return void
     */
    public function getSFuelBalance(): void
    {
        $address = $this->getOpt('address') ?? null;
        $username = $this->getOpt('username') ?? null;

        if (!($address xor $username)) {
            $this->out('You must EITHER a username or address, but not both');
            return;
        }

        $user = null;
        if (!$address) {
            $user = $this->entitiesBuilder->getByUserByIndex($username) ?? null;
        }

        $balance = $this->skaleTools->getSFuelBalance(
            address: $address ?? null,
            user: $user ?? null,
            useCache: false
        );

        $this->out("address:\t" . ($address ? $address : $this->skaleTools->getCustodialWalletAddress($user)));
        $this->out("balance:\t" . $balance . " wei");
    }

    /**
     * Send sFuel (SKALE network Ether) from a custodial wallet to either
     * another custodial wallet, or an external Ethereum address.
     * @example
     * - php cli.php Skale sendSFuel --senderUsername=minds --receiverAddress=0x00000...
     * - php cli.php Skale sendSFuel --senderUsername=minds --receiverUsername=testuser
     * - php cli.php Skale sendSFuel --senderUsername=minds --receiverUsername=testuser --amountWei=220000000000
     * @return void
     */
    public function sendSFuel(): void
    {
        $senderUsername = $this->getOpt('senderUsername') ?? null;
        $receiverUsername = $this->getOpt('receiverUsername') ?? null;
        $receiverAddress = $this->getOpt('receiverAddress') ?? null;
        $amountWei = $this->getOpt('amountWei') ?? null;

        // validate required opts were passed in.
        if (!$senderUsername || !($receiverAddress xor $receiverUsername)) {
            $this->out('You must set a sender and either a receiverAddress or receiverUsername');
            return;
        }

        // build receiver and if no receiverAddress was passed in, sender too.
        $sender = $this->entitiesBuilder->getByUserByIndex($senderUsername);
        $receiver = null;
        if (!$receiverAddress) {
            $receiver = $this->entitiesBuilder->getByUserByIndex($receiverUsername);
        }

        if (!$sender || (!isset($receiver) && !$receiver && !$receiverAddress)) {
            $this->out('Unable to construct both sender and receiver, or missing receiver address');
            return;
        }

        $txHash = $this->skaleTools->sendSFuel(
            sender: $sender,
            receiverAddress: $receiverAddress ?? null,
            receiver: $receiver ?? null,
            amountWei: $amountWei ?? null
        );

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
    public function sendTokens(): void
    {
        $senderUsername = $this->getOpt('senderUsername') ?? null;
        $receiverUsername = $this->getOpt('receiverUsername') ?? null;
        $receiverAddress = $this->getOpt('receiverAddress') ?? null;
        $amountWei = $this->getOpt('amountWei') ?? false;

        // validate required opts were passed in.
        if (!$senderUsername || !$amountWei || !($receiverAddress xor $receiverUsername)) {
            $this->out('You must set a sender, amount and EITHER a receiverAddress or receiverUsername');
            return;
        }

        // build sender and if no receiverAddress was passed in, receiver too.
        $sender = $this->entitiesBuilder->getByUserByIndex($senderUsername);
        $receiver = null;

        if (!$receiverAddress) {
            $receiver = $this->entitiesBuilder->getByUserByIndex($receiverUsername);
        }

        if (!$sender || (!isset($receiver) && !$receiver && !$receiverAddress)) {
            $this->out('Unable to construct both sender and receiver, or missing receiver address');
            return;
        }
    
        // prepare and send transaction.
        $txHash = $this->skaleTools->sendTokens(
            sender: $sender,
            receiverAddress: $receiverAddress ?? null,
            receiver: $receiver ?? null,
            amountWei: $amountWei ?? null
        );

        $this->out('Sent with tx hash '. $txHash);
    }
}
