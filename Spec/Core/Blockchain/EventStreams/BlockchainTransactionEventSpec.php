<?php

namespace Spec\Minds\Core\Blockchain\EventStreams;

use PhpSpec\ObjectBehavior;

class BlockchainTransactionEventSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Blockchain\EventStreams\BlockchainTransactionEvent');
    }

    public function it_is_should_set_and_get_timestamp()
    {
        $time = time();
        $this->setTimestamp($time);
        $this->getTimestamp()->shouldBe($time);
    }

    public function it_is_should_set_and_get_sender_guid()
    {
        $senderGuid = '012345678912345';
        $this->setSenderGuid($senderGuid);
        $this->getSenderGuid()->shouldBe($senderGuid);
    }

    public function it_is_should_set_and_get_receiver_guid()
    {
        $receiverGuid = '012345678912345';
        $this->setReceiverGuid($receiverGuid);
        $this->getReceiverGuid()->shouldBe($receiverGuid);
    }

    public function it_is_should_set_and_get_transaction_id()
    {
        $txId = '0x0123456789abc';
        $this->setTransactionId($txId);
        $this->getTransactionId()->shouldBe($txId);
    }

    public function it_is_should_set_and_get_wallet_address()
    {
        $walletAddress = '0xabcde1234567689';
        $this->setWalletAddress($walletAddress);
        $this->getWalletAddress()->shouldBe($walletAddress);
    }

    public function it_is_should_set_and_get_contract()
    {
        $contract = 'wire';
        $this->setContract($contract);
        $this->getContract()->shouldBe($contract);
    }

    public function it_is_should_set_and_get_amount_wei()
    {
        $amountWei = '10000000000000';
        $this->setAmountWei($amountWei);
        $this->getAmountWei()->shouldBe($amountWei);
    }
}
