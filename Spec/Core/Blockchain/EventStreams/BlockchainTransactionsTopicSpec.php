<?php

namespace Spec\Minds\Core\Blockchain\EventStreams;

use Minds\Core\Blockchain\EventStreams\BlockchainTransactionEvent;
use Minds\Core\Blockchain\EventStreams\BlockchainTransactionsTopic;
use PhpSpec\ObjectBehavior;

class BlockchainTransactionsTopicSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(BlockchainTransactionsTopic::class);
    }

    public function it_should_send_event()
    {
        $senderGuid = '012345';
        $receiverGuid = '98765';
        $transactionId = '0x1111111';
        $skaleTxHash = '0x0000000';
        $amountWei ='100000000000';
        $walletAddress = '0x2222222';
        $contract = 'offchain:wire';

        $event = new BlockchainTransactionEvent();
        $event->setSenderGuid($senderGuid)
            ->setReceiverGuid($receiverGuid)
            ->setTransactionId($transactionId)
            ->setSkaleTransactionId($skaleTxHash)
            ->setWalletAddress($walletAddress)
            ->setContract($contract)
            ->setAmountWei($amountWei);

        $this->send($event)
            ->shouldBe(true);
    }
}
