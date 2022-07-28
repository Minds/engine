<?php

namespace Minds\Core\Blockchain\Skale\BalanceSynchronizer;

use Minds\Core\Blockchain\Wallets\OffChain\Balance as OffchainBalance;
use Minds\Core\Blockchain\Wallets\OffChain\Transactions as OffchainTransactions;
use Minds\Core\Blockchain\Skale\Tools as SkaleTools;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Exceptions\ServerErrorException;

/**
 * Used to synchronize the instance users skale balance with their offchain balance
 * by getting the users skale MINDS token balances offset from their offchain balance.
 * Then to make the balances equal, either:
 * - Sends more SKALE MINDS from the balance sync user to the instance users wallet.
 * - Sends SKALE MINDS balance FROM the instance users wallet to the balance sync users wallet.
 */
class BalanceSynchronizer
{
    /**
     * @var User|null $user - instance user - set via withUsers,
     * not directly - public encapsulation only for testability.
     */
    public ?User $user = null;

    /**
     * Constructor.
     * @param SkaleTools|null $skaleTools - tools for interacting with skale chain.
     * @param DifferenceCalculator|null $differenceCalculator - calculator for balance differences.
     * @param EntitiesBuilder|null $entitiesBuilder - building user entities.
     * @param OffchainBalance|null $offchainBalance - getting offchain balance.
     * @param Config|null $config - config values.
     */
    public function __construct(
        private ?SkaleTools $skaleTools = null,
        private ?DifferenceCalculator $differenceCalculator = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?OffchainBalance $offchainBalance = null,
        private ?OffchainTransactions $offchainTransactions = null,
        private ?Config $config = null
    ) {
        $this->skaleTools ??= Di::_()->get('Blockchain\Skale\Tools');
        $this->differenceCalculator ??= Di::_()->get('Blockchain\Skale\DifferenceCalculator');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->offchainBalance ??= Di::_()->get('Blockchain\Wallets\OffChain\Balance');
        $this->offchainTransactions ??= Di::_()->get('Blockchain\Wallets\OffChain\Transactions');
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Construct a new instance with user.
     * @param User $user - user to get new instance with.
     * @return BalanceSynchronizer - new instance of BalanceSynchronizer.
     */
    public function withUser(User $user): BalanceSynchronizer
    {
        $instance = clone $this;
        $instance->user = $user;
        return $instance;
    }

    /**
     * Get instance user.
     * @return User|null $user - instance user.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Sync instance users SKALE MINDS token balance to match their offchain balance.
     * @param bool $dryRun - whether only adjustment result should be returned without sending tx.
     * @throws SyncExcludedUserException - if user is excluded from sync.
     * @return AdjustmentResult|null - Object containing result of adjustment or null, if no adjustment made.
     */
    public function syncSkale(bool $dryRun = false): ?AdjustmentResult
    {
        if (in_array($this->user->getGuid(), $this->getExcludedUserGuids(), true)) {
            throw new SyncExcludedUserException('Attempted to sync balance of excluded user: '.$this->user->getUsername());
        }

        $differenceCalculator = $this->buildDifferenceCalculator();
        $balanceDifference = $differenceCalculator->calculateSkaleDiff();

        if ($balanceDifference->eq(0)) {
            return null;
        }

        $txHash = '';

        if (!$dryRun) {
            if ($balanceDifference->lt(0)) {
                $txHash = $this->skaleTools->sendTokens(
                    sender: $this->getBalanceSyncUser(),
                    receiver: $this->user,
                    amountWei: $balanceDifference->neg()->toString(),
                    waitForConfirmation: false,
                    checkSFuel: false
                );
            }

            if ($balanceDifference->gt(0)) {
                $txHash = $this->skaleTools->sendTokens(
                    sender: $this->user,
                    receiver: $this->getBalanceSyncUser(),
                    amountWei: $balanceDifference->toString(),
                    waitForConfirmation: false,
                    checkSFuel: true
                );
            }
        }

        return (new AdjustmentResult())
            ->setTxHash($txHash)
            ->setDifferenceWei($balanceDifference)
            ->setUsername($this->user->getUsername());
    }

    /**
     * Sync instance users offchain token balance to match their SKALE MINDS balance.
     * @param bool $dryRun - whether only adjustment result should be returned without sending tx.
     * @param bool $bypassUserExcludes - if true, excluded user list will NOT be checked.
     * @throws SyncExcludedUserException - if user is excluded from sync.
     * @return AdjustmentResult|null - Object containing result of adjustment or null, if no adjustment made.
     */
    public function syncOffchain(bool $dryRun = false, bool $bypassUserExcludes = false): ?AdjustmentResult
    {
        if (!$bypassUserExcludes && in_array($this->user->getGuid(), $this->getExcludedUserGuids(), true)) {
            throw new SyncExcludedUserException('Attempted to sync balance of excluded user: '.$this->user->getUsername());
        }

        $differenceCalculator = $this->buildDifferenceCalculator();
        $balanceDifference = $differenceCalculator->calculateOffchainDiff();

        if ($balanceDifference->eq(0)) {
            return null;
        }

        if (!$dryRun) {
            $amountWei = abs($balanceDifference->toString());

            if ($balanceDifference->lt(0)) {
                $sender = $this->getBalanceSyncUser();
                $receiver = $this->user;
            }

            if ($balanceDifference->gt(0)) {
                $sender = $this->user;
                $receiver = $this->getBalanceSyncUser();
            }

            $this->offchainTransactions
                ->setAmount($amountWei)
                ->setType('wire')
                ->setUser($receiver)
                ->setBypassSkaleMirror(true)
                ->setData([
                    'amount' => (string) $amountWei,
                    'sender_guid' => (string) $sender->getGuid(),
                    'receiver_guid' => (string) $receiver->getGuid()
                ])
                ->transferFrom($sender);
        }

        return (new AdjustmentResult())
            ->setTxHash('')
            ->setDifferenceWei($balanceDifference)
            ->setUsername($this->user->getUsername());
    }

    /**
     * Reset balance of a users custodial wallet to 0.
     * Only available in development mode.
     * @throws ServerErrorException - if development_mode isn't on.
     * @throws SyncExcludedUser - if user is excluded from sync operations.
     * @return string|null
     */
    public function resetBalance(): ?string
    {
        if (!($this->config->get('blockchain')['skale']['development_mode'] ?? false)) {
            throw new ServerErrorException('Balances can only be reset in SKALE development_mode');
        }

        if (in_array($this->user->getGuid(), $this->getExcludedUserGuids(), true)) {
            throw new SyncExcludedUserException('Attempted to sync balance of excluded user: '.$this->user->getUsername());
        }

        $receiver = $this->getBalanceSyncUser();
    
        $amountWei = $this->getSkaleTokenBalance();

        if ($amountWei === "0") {
            return '';
        }

        return $this->skaleTools->sendTokens(
            sender: $this->user,
            receiver: $receiver,
            amountWei: $amountWei ?? null,
            checkSFuel: true,
            waitForConfirmation: false,
        );
    }

    /**
     * Builds a new instance of the balance calculator with the instance users balances.
     * @return DifferenceCalculator - difference calculator.
     */
    public function buildDifferenceCalculator(): DifferenceCalculator
    {
        return $this->differenceCalculator->withBalances(
            skaleTokenBalance: $this->getSkaleTokenBalance(),
            offchainBalance: $this->getOffchainTokenBalance()
        );
    }

    /**
     * Gets the instance users SKALE MINDS token balance.
     * @return string the instance users SKALE MINDS token balance.
     */
    private function getSkaleTokenBalance(): string
    {
        return $this->skaleTools->getTokenBalance(
            user: $this->user,
            useCache: false
        )  ?? '0';
    }

    /**
     * Gets the instance users offchain balance.
     * @return string the instance users offchain balance.
     */
    private function getOffchainTokenBalance(): string
    {
        return $this->offchainBalance
            ->setUser($this->user)
            ->get() ?? '0';
    }

    /**
     * Gets user responsible for sending and receiving tokens in relation to balance updates.
     * @throws ServerErrorException - if no user is found.
     * @return User user responsible for sending and receiving tokens in relation to balance updates.
     */
    public function getBalanceSyncUser(): User
    {
        $balanceSyncUserGuid = $this->config->get('blockchain')['skale']['balance_sync_user_guid'] ?? '100000000000000519';
        $user = $this->entitiesBuilder->single($balanceSyncUserGuid);
        if (!$user || !$user instanceof User) {
            throw new ServerErrorException('Unable to find main balance sync user');
        }
        return $user;
    }

    /**
     * Gets sync excluded user GUIDs from config. Users should be excluded when
     * there is allowed to be an offchain / skale balance discrepancy - useful
     * for example for wallets when testing, else a distribution wallet will try to send
     * tokens to its self when it realises it doesn't have a matching offchain balance.
     * @return array array of user guids excluded from sync.
     */
    public function getExcludedUserGuids(): array
    {
        return $this->config->get('blockchain')['skale']['sync_excluded_users'] ?? [];
    }
}
