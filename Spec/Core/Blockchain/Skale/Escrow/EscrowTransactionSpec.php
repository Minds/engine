<?php

namespace Spec\Minds\Core\Blockchain\Skale\Escrow;

use PhpSpec\ObjectBehavior;
use Minds\Core\Blockchain\Skale\Escrow\EscrowTransaction;
use Minds\Entities\User;

class EscrowTransactionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(EscrowTransaction::class);
    }

    public function it_should_set_and_get_sender(User $user)
    {
        $this->setSender($user);
        $this->getSender()->shouldBe($user);
    }

    public function it_should_set_and_get_receiver(User $user)
    {
        $this->setReceiver($user);
        $this->getReceiver()->shouldBe($user);
    }

    public function it_should_set_and_get_tx_hash()
    {
        $txHash = '0x011111111';
        $this->setTxHash($txHash);
        $this->getTxHash()->shouldBe($txHash);
    }
}
