<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Interfaces;
use Minds\Core\Blockchain\Skale\Keys as SkaleKeys;
use Minds\Core\Blockchain\Skale\Tools as SkaleTools;
use Minds\Core\Blockchain\Wallets\OffChain\Balance;
use Minds\Core\Blockchain\Skale\BalanceSynchronizer\BalanceSynchronizer;
use Minds\Core\Blockchain\Skale\BalanceSynchronizer\SyncExcludedUserException;
use Minds\Core\Blockchain\Transactions\ScrollRepository;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Nostr\PocSync;
use Minds\Entities\User;

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
     * @param Balance|null $offchainBalance - used to get a users offchain balance.
     * @param BalanceSynchronizer|null $balanceSynchronizer - used to sync users balance with offchain.
     * @param Config|null $config - config values.
     */
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?PocSync $nostrPocSync = null,
        private ?SkaleKeys $skaleKeys = null,
        private ?SkaleTools $skaleTools = null,
        private ?Balance $offchainBalance = null,
        private ?BalanceSynchronizer $balanceSynchronizer = null,
        private ?ScrollRepository $scrollRepository = null,
        private ?Config $config = null
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->nostrPocSync ??= Di::_()->get('Nostr\PocSync');
        $this->skaleKeys ??= Di::_()->get('Blockchain\Skale\Keys');
        $this->skaleTools ??= Di::_()->get('Blockchain\Skale\Tools');
        $this->offchainBalance ??= Di::_()->get('Blockchain\Wallets\OffChain\Balance');
        $this->balanceSynchronizer ??= Di::_()->get('Blockchain\Skale\BalanceSynchronizer');
        $this->scrollRepository ??= Di::_()->get('Blockchain\Transactions\ScrollRepository');
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

        $skaleMindsBalanceWei = $this->skaleTools->getTokenBalance(
            address: $address ?? null,
            user: $user ?? null,
            useCache: false
        );

        $this->out("Ethereum address:\t" . ($address ? $address : $this->skaleTools->getCustodialWalletAddress($user)));

        if (!$address && $user) {
            $offchainBalanceWei = $this->offchainBalance
                ->setUser($user)
                ->get();
            $this->out("Offchain balance:\t".$offchainBalanceWei." wei");
        }

        $this->out("SKALE MINDS balance:\t" . $skaleMindsBalanceWei . " wei");
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

    /**
     * Sync SKALE MINDS token balance of a user with their offchain balance,
     * by sending SKALE MINDS to or from their custodial wallet.
     * @example
     * - php cli.php Skale syncBalance --username=userA
     * - php cli.php Skale syncBalance --username=userA --dryRun
     * - php cli.php Skale syncBalance --username=userA --verbose
     * @return void
     */
    public function syncBalance(): void
    {
        $username = $this->getOpt('username') ?? null;
        $dryRun = $this->getOpt('dryRun') ?? false;
        $verbose = $this->getOpt('verbose') ?? false;

        if (!$username) {
            $this->out('You must set a user to sync the balance of');
            return;
        }

        $user = $this->entitiesBuilder->getByUserByIndex($username);

        if (!$user) {
            $this->out("User $username not found");
        }

        $balanceSynchronizer = $this->balanceSynchronizer->withUser($user);

        if ($verbose || $dryRun) {
            $this->dumpBalanceSynchronizerInfo($balanceSynchronizer);
        }

        if (!$dryRun) {
            $adjustmentResult = $balanceSynchronizer->sync();

            if ($adjustmentResult) {
                $this->out($adjustmentResult);
            }
        }
    }

    /**
     * Sync all users balances. Can be run with dryRun flag to see only the details
     * on users balances that would be changed.
     * @example
     * - php cli.php Skale syncAll
     * - php cli.php Skale syncAll --dryRun
     * - php cli.php Skale syncAll --verbose
     * @return void
     */
    public function syncAll()
    {
        $dryRun = $this->getOpt('dryRun') ?? false;
        $verbose = $this->getOpt('verbose') ?? false;

        $this->out('Beginning to iterate over all distinct offchain users - this may take a while...');

        foreach ($this->scrollRepository->getDistinctOffchainUserGuids() as $userGuid) {
            try {
                $timeStart = microtime(true);
                $userGuid = $userGuid['user_guid']->value();

                // Turn off error reporting temporarily to avoid log spam with weird entities.
                error_reporting(0);
                $user = $this->entitiesBuilder->single($userGuid);
                error_reporting(E_ALL);

                if (!$user || !$user instanceof User) {
                    if ($verbose) {
                        $this->out("Not a valid user: $userGuid");
                    }
                    continue;
                }

                if ($verbose) {
                    $username = $user->getUsername();
                    $this->out("Checking $username ($userGuid)");
                }

                $balanceSynchronizer = $this->balanceSynchronizer->withUser($user);
                if ($dryRun || $verbose) {
                    $this->dumpBalanceSynchronizerInfo(
                        balanceSynchronizer: $balanceSynchronizer,
                        onlyDiscrepancies: true
                    );
                    if ($dryRun) {
                        continue;
                    }
                }

                $adjustmentResult = $balanceSynchronizer->sync();

                if ($adjustmentResult) {
                    $this->out('------------------');
                    $this->out($adjustmentResult);
                    $this->out('------------------');
                }

                if ($verbose) {
                    $this->out(microtime(true) - $timeStart . ' seconds elapsed');
                }
            } catch (SyncExcludedUserException $e) {
                if ($verbose) {
                    $this->out($e->getMessage());
                }
            } catch (\Exception $e) {
                $this->out($e);
            }
        }
        $this->out('Finished!');
    }

    /**
     * Reset all balances for all offchain users - will only work in SKALE development_mode.
     * @example
     * - php cli.php Skale resetAllBalances --verbose
     * - php cli.php Skale resetAllBalances
     * @return void
     */
    public function resetAllBalances()
    {
        $verbose = $this->getOpt('verbose') ?? false;

        $this->out('Beginning to iterate over all distinct offchain users - this may take a while...');
        $timeStart = microtime(true);

        foreach ($this->scrollRepository->getDistinctOffchainUserGuids() as $userGuid) {
            try {
                $timeStart = microtime(true);

                $userGuid = $userGuid['user_guid']->value();

                // Turn off error reporting temporarily to avoid log spam with weird entities.
                error_reporting(0);
                $user = $this->entitiesBuilder->single($userGuid);
                error_reporting(E_ALL);

                if (!$user || !$user instanceof User) {
                    if ($verbose) {
                        $this->out("Not a valid user: $userGuid");
                    }
                    continue;
                }

                $username = $user->getUsername();

                if ($verbose) {
                    $this->out("Checking $username ($userGuid)");
                }

                $txHash = $this->balanceSynchronizer->withUser($user)
                    ->resetBalance();

                if ($txHash) {
                    $this->out("Reset $username ($userGuid) balance with: $txHash");
                }

                if ($verbose) {
                    $this->out(microtime(true) - $timeStart . ' seconds elapsed');
                }
            } catch (SyncExcludedUserException $e) {
                if ($verbose) {
                    $this->out($e->getMessage());
                }
            } catch (\Exception $e) {
                $this->out($e);
            }
        }
        $this->out('Finished!');
    }

    /**
     * Dumps info from BalanceSynchronizer instance.
     * @param BalanceSynchronizer $balanceSynchronizer - instance to dump info from.
     * @param boolean $onlyDiscrepancies - if output should only be made when there is a discrepancy.
     * @return void
     */
    private function dumpBalanceSynchronizerInfo(BalanceSynchronizer $balanceSynchronizer, bool $onlyDiscrepancies = false): void
    {
        /** @var BalanceCalculator */
        $differenceCalculator = $balanceSynchronizer->buildDifferenceCalculator();
        $balanceDifferenceWei = $differenceCalculator->calculateSkaleDiff();

        if (!$onlyDiscrepancies || !$balanceDifferenceWei->eq(0)) {
            $this->out('------------------');
            $offchainBalanceWei = $differenceCalculator->getOffchainBalance();
            $skaleTokenBalanceWei = $differenceCalculator->getSkaleTokenBalance();
            $username = $balanceSynchronizer->getUser()->getUsername();
            $guid = $balanceSynchronizer->getUser()->getGuid();

            $this->out("Offchain balance for $username ($guid):\t$offchainBalanceWei wei");
            $this->out("SK Token balance for $username ($guid):\t$skaleTokenBalanceWei wei");
            $this->out("SKALE discrepancy for $username ($guid):\t$balanceDifferenceWei wei");
            $this->out('------------------');
        }
    }
}
