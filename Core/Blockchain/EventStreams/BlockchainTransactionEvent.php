<?php

namespace Minds\Core\Blockchain\EventStreams;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\TimebasedEventTrait;

/**
 * Event type for a Blockchain transaction event.
 */
class BlockchainTransactionEvent implements EventInterface
{
    // inherit time-based setters and getters.
    use TimebasedEventTrait;

    /** @var string - guid of the sender */
    protected string $senderGuid;

    /** @var string - guid of the receiver */
    protected string $receiverGuid;

    /** @var string - id of the transaction */
    protected string $transactionId;

    /** @var string - amount transacted in wei */
    protected string $amountWei;

    /** @var string - wallet address - if offchain, should be `offchain` */
    protected string $walletAddress;

    /** @var string contract called - for offchain, should be like `offchain:wire`*/
    protected string $contract;

    /**
     * Sets sender guid.
     * @param string $senderGuid - guid of sender.
     * @return self
     */
    public function setSenderGuid(string $senderGuid): self
    {
        $this->senderGuid = $senderGuid;
        return $this;
    }

    /**
     * Gets sender guid.
     * @return string - guid of sender.
     */
    public function getSenderGuid(): string
    {
        return $this->senderGuid;
    }

    /**
     * Sets receiver guid.
     * @param string $receiverGuid - guid of receiver.
     * @return self
     */
    public function setReceiverGuid(string $receiverGuid): self
    {
        $this->receiverGuid = $receiverGuid;
        return $this;
    }

    /**
     * Gets receiver guid.
     * @return string - guid of receiver.
     */
    public function getReceiverGuid(): string
    {
        return $this->receiverGuid;
    }

    /**
     * Sets transaction id.
     * @param string $transactionId - txid / hash of transaction.
     * @return self
     */
    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * Gets transaction id.
     * @return string txid / hash of transaction.
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * Sets wallet address.
     * @param string $walletAddress - wallet address.
     * @return self
     */
    public function setWalletAddress(string $walletAddress): self
    {
        $this->walletAddress = $walletAddress;
        return $this;
    }

    /**
     * Gets wallet address.
     * @return string wallet address.
     */
    public function getWalletAddress(): string
    {
        return $this->walletAddress;
    }

    /**
     * Sets contract. Can be like offchain:wire for offchain.
     * @param string $contract - contract to set.
     * @return self
     */
    public function setContract(string $contract): self
    {
        $this->contract = $contract;
        return $this;
    }

    /**
     * Gets contract. Can be like offchain:wire for offchain.
     * @return string contract.
     * @return self
     */
    public function getContract(): string
    {
        return $this->contract;
    }

    /**
     * Set amount transacted in wei.
     * @param string $amountWei - amount transacted in wei.
     * @return self
     */
    public function setAmountWei(string $amountWei): self
    {
        $this->amountWei = $amountWei;
        return $this;
    }

    /**
     * Get amount transacted in wei.
     * @return string amount transacted in wei.
     */
    public function getAmountWei(): string
    {
        return $this->amountWei;
    }
}
