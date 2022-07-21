<?php

namespace Spec\Minds\Core\Blockchain\Skale\BalanceSynchronizer;

use Minds\Core\Blockchain\Skale\BalanceSynchronizer\DifferenceCalculator;
use PhpSpec\ObjectBehavior;

class DifferenceCalculatorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(DifferenceCalculator::class);
    }

    public function it_should_construct_new_instance()
    {
        $offchainBalance = '1000';
        $skaleTokenBalance = '100';
        $this->withBalances(
            offchainBalance: $offchainBalance,
            skaleTokenBalance: $skaleTokenBalance
        )->shouldHaveType(DifferenceCalculator::class);
    }

    public function it_should_calculate_correctly_when_skale_has_more()
    {
        $offchainBalance = '100';
        $skaleTokenBalance = '1000';

        $instance = $this->withBalances(
            offchainBalance: $offchainBalance,
            skaleTokenBalance: $skaleTokenBalance
        );

        $instance->calculateSkaleDiff()->toString()->shouldBe('900');
        $instance->calculateOffchainDiff()->toString()->shouldBe('-900');
    }

    public function it_should_calculate_correctly_when_offchain_has_more()
    {
        $offchainBalance = '1000';
        $skaleTokenBalance = '100';

        $instance = $this->withBalances(
            offchainBalance: $offchainBalance,
            skaleTokenBalance: $skaleTokenBalance
        );

        $instance->calculateSkaleDiff()->toString()->shouldBe('-900');
        $instance->calculateOffchainDiff()->toString()->shouldBe('900');
    }

    public function it_should_calculate_correctly_when_balances_equal()
    {
        $offchainBalance = '100';
        $skaleTokenBalance = '100';

        $instance = $this->withBalances(
            offchainBalance: $offchainBalance,
            skaleTokenBalance: $skaleTokenBalance
        );

        $instance->calculateSkaleDiff()->toString()->shouldBe('0');
        $instance->calculateOffchainDiff()->toString()->shouldBe('0');
    }
}
