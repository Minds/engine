<?php

namespace Minds\Core\Blockchain\Transactions\Delegates;

use Minds\Core\Blockchain\EventStreams\BlockchainTransactionEvent;
use Minds\Core\Blockchain\EventStreams\BlockchainTransactionsTopic;
use Minds\Core\Blockchain\Transactions\Transaction;

/**
 * Delegate for firing transaction events.
 */
class TransactionEventDelegate
{
    /**
     * Constructor.
     * @param BlockchainTransactionsTopic|null $blockchainTransactionsTopic - topic to fire events to.
     */
    public function __construct(
        private ?BlockchainTransactionsTopic $blockchainTransactionsTopic = null
    ) {
        $this->blockchainTransactionsTopic ??= new BlockchainTransactionsTopic();
    }

    /**
     * Called on transaction add. Sends event to topic.
     * @param Transaction $transaction - added transaction.
     * @return void
     */
    public function onAdd(Transaction $transaction): void
    {
        $this->blockchainTransactionsTopic->send(
            $this->buildEvent($transaction)
        );
    }

    /**
     * Construct an transaction event.
     * @param Transaction $transaction - transaction to construct event from.
     * @return BlockchainTransactionEvent - built event object ready to be fired to topic.
     */
    private function buildEvent(Transaction $transaction): BlockchainTransactionEvent
    {
        return (new BlockchainTransactionEvent())
            ->setSenderGuid($transaction->getData()['sender_guid'] ?? $transaction->getUserGuid())
            ->setReceiverGuid($transaction->getData()['receiver_guid'] ?? '')
            ->setTransactionId($transaction->getTx())
            ->setWalletAddress($transaction->getWalletAddress())
            ->setContract($transaction->getContract())
            ->setAmountWei($transaction->getAmount());
    }
}
