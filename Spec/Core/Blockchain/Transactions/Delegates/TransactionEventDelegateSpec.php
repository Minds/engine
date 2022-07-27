<?php

namespace Spec\Minds\Core\Blockchain\Transactions\Delegates;

use Minds\Core\Blockchain\EventStreams\BlockchainTransactionsTopic;
use Minds\Core\Blockchain\Transactions\Delegates\TransactionEventDelegate;
use Minds\Core\Blockchain\Transactions\Transaction;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TransactionEventDelegateSpec extends ObjectBehavior
{
    /** @var BlockchainTransactionsTopic */
    private $blockchainTransactionsTopic;

    public function let(
        BlockchainTransactionsTopic $blockchainTransactionsTopic,
    ) {
        $this->blockchainTransactionsTopic = $blockchainTransactionsTopic;
        $this->beConstructedWith($blockchainTransactionsTopic);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TransactionEventDelegate::class);
    }

    public function it_should_add_an_event(Transaction $transaction)
    {
        $senderGuid = '123';
        $receiverGuid = '321';
        $txId = '0x1234567890abcdef';
        $walletAddress = '0xabcdef1234567890';
        $contract = 'offchain:wire';
        $amountWei = '10000000000000000';

        $transaction->getData()
            ->shouldBeCalled()
            ->willReturn([
                'sender_guid' => $senderGuid,
                'receiver_guid' => $receiverGuid
            ]);
            
        $transaction->getTx()
            ->shouldBeCalled()
            ->willReturn($txId);

        $transaction->getWalletAddress()
            ->shouldBeCalled()
            ->willReturn($walletAddress);

        $transaction->getContract()
            ->shouldBeCalled()
            ->willReturn($contract);

        $transaction->getAmount()
            ->shouldBeCalled()
            ->willReturn($amountWei);

        $this->blockchainTransactionsTopic->send(
            Argument::that(
                function ($arg) use (
                    $senderGuid,
                    $receiverGuid,
                    $txId,
                    $walletAddress,
                    $contract,
                    $amountWei
                ) {
                    return $arg->getSenderGuid() === $senderGuid &&
                    $arg->getReceiverGuid() === $receiverGuid &&
                    $arg->getTransactionId() === $txId &&
                    $arg->getAmountWei() === $amountWei &&
                    $arg->getContract() === $contract &&
                    $arg->getWalletAddress() === $walletAddress;
                }
            )
        )
            ->shouldBeCalled();

        $this->onAdd($transaction);
    }
}
