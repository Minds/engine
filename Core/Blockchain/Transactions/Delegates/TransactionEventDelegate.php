<?php

namespace Minds\Core\Blockchain\Transactions\Delegates;

use Minds\Core\Blockchain\EventStreams\BlockchainTransactionEvent;
use Minds\Core\Blockchain\EventStreams\BlockchainTransactionsTopic;
use Minds\Core\Blockchain\Transactions\Transaction;

class TransactionEventDelegate
{
    public function __construct(
        private ?BlockchainTransactionsTopic $blockchainTransactionsTopic = null
    ) {
        $this->blockchainTransactionsTopic ??= new BlockchainTransactionsTopic();
    }

    public function onAdd(Transaction $transaction): void
    {
        $this->blockchainTransactionsTopic->send(
            $this->buildEvent($transaction)
        );
    }

    private function buildEvent(Transaction $transaction): BlockchainTransactionEvent
    {
        return (new BlockchainTransactionEvent())
            ->setSenderGuid($transaction->getData()['sender_guid'])
            ->setReceiverGuid($transaction->getData()['receiver_guid'])
            ->setTransactionId($transaction->getTx())
            ->setWalletAddress($transaction->getWalletAddress())
            ->setContract($transaction->getContract())
            ->setAmountWei($transaction->getAmount());
    }
}
