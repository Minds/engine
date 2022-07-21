<?php

namespace Minds\Core\Blockchain\Skale\BalanceSynchronizer;

use Minds\Core\Util\BigNumber;
use Minds\Traits\MagicAttributes;

/**
 * Calculator to calculate the difference between offchain and skale token balances.
 */
class DifferenceCalculator
{
    use MagicAttributes;

    /** @var string|null offchain balance in wei */
    private ?string $offchainBalance = null;

    /** @var string|null skale MINDS token balance in wei */
    private ?string $skaleTokenBalance = null;

    /**
     * Construct a new instance with balances.
     * @param string $offchainBalance - offchain balance - offchain balance in wei.
     * @param string $skaleTokenBalance - skale MINDS token balance in wei.
     * @return DifferenceCalculator - new instance of self.
     */
    public function withBalances(string $offchainBalance, string $skaleTokenBalance): DifferenceCalculator
    {
        $instance = clone $this;
        $instance->setOffchainBalance($offchainBalance);
        $instance->setSkaleTokenBalance($skaleTokenBalance);
        return $instance;
    }

    /**
     * Calculate SKALE MINDS token balances offset against offchain token balance.
     * @return BigNumber SKALE MINDS token balances offset against offchain token balance.
     */
    public function calculateSkaleDiff(): BigNumber
    {
        $offchainBalance = new BigNumber($this->getOffchainBalance());
        $skaleTokenBalance = new BigNumber($this->getSkaleTokenBalance());
        return $skaleTokenBalance->sub($offchainBalance);
    }

    /**
     * Calculate offchain balances offset against SKALE MINDS token balance.
     * @return BigNumber Offchain balances offset against SKALE MINDS token balance.
     */
    public function calculateOffchainDiff(): BigNumber
    {
        $offchainBalance = new BigNumber($this->getOffchainBalance());
        $skaleTokenBalance = new BigNumber($this->getSkaleTokenBalance());
        return $offchainBalance->sub($skaleTokenBalance);
    }
}
