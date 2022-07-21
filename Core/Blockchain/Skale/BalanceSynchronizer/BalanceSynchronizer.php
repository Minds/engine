<?php

namespace Minds\Core\Blockchain\Skale\BalanceSynchronizer;

use Minds\Core\Blockchain\Wallets\Offchain\Balance as OffchainBalance;
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
        private ?Config $config = null
    ) {
        $this->skaleTools ??= Di::_()->get('Blockchain\Skale\Tools');
        $this->differenceCalculator ??= Di::_()->get('Blockchain\Skale\DifferenceCalculator');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->offchainBalance ??= Di::_()->get('Blockchain\Wallets\OffChain\Balance');
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
     * Sync instance users SKALE MINDS token balance to match their offchain balance.
     * @return AdjustmentResult|null - Object containing result of adjustment or null, if no adjustment made.
     */
    public function sync(): ?AdjustmentResult
    {
        $differenceCalculator = $this->buildDifferenceCalculator();
        $balanceDifference = $differenceCalculator->calculateSkaleDiff();

        if ($balanceDifference->eq(0)) {
            return null;
        }

        if ($balanceDifference->lt(0)) {
            $txHash = $this->skaleTools->sendTokens(
                sender: $this->getBalanceSyncUser(),
                receiver: $this->user,
                amountWei: $balanceDifference->neg()->toString()
            );
        }

        if ($balanceDifference->gt(0)) {
            $txHash = $this->skaleTools->sendTokens(
                sender: $this->user,
                receiver: $this->getBalanceSyncUser(),
                amountWei: $balanceDifference->toString()
            );
        }

        return (new AdjustmentResult())
            ->setTxHash($txHash)
            ->setDifferenceWei($balanceDifference)
            ->setUsername($this->user->getUsername());
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
    private function getBalanceSyncUser(): User
    {
        $balanceSyncUserGuid = $this->config->get('blockchain')['skale']['balance_sync_user_guid'] ?? '100000000000000519';
        $user = $this->entitiesBuilder->single($balanceSyncUserGuid);
        if (!$user || !$user instanceof User) {
            throw new ServerErrorException('Unable to find main balance sync user');
        }
        return $user;
    }
}
