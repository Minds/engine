<?php

namespace Spec\Minds\Core\Blockchain\Skale\BalanceSynchronizer;

use Minds\Core\Blockchain\Skale\BalanceSynchronizer\AdjustmentResult;
use PhpSpec\ObjectBehavior;

class AdjustmentResultSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(AdjustmentResult::class);
    }

    public function it_should_construct_string_representation_of_class()
    {
        $txHash = '0x00';
        $differenceWei = '100000';
        $username = 'testuser';

        $this->setTxHash($txHash)
            ->setDifferenceWei($differenceWei)
            ->setUsername($username)
            ->__toString()->shouldBe('User: testuser, SKALE balance offset: 100000 wei, Correction TX: 0x00');
    }
}
