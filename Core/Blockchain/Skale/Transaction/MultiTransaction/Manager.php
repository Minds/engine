<?php

namespace Minds\Core\Blockchain\Skale\Transaction\MultiTransaction;

use Minds\Core\Blockchain\Skale\Transaction\Manager as TransactionManager;
use Minds\Entities\User;

/**
 * To allow for multiple transactions to be sent to different receivers,
 * this class intentionally allows the use of the transaction manager
 * without enforcing the creation of a new instance every time the recipient changes,
 * allowing for nonce incrementation to be tracked in memory between receiver switches.
 */
class Manager extends TransactionManager
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Set instance sender.
     * @param User $sender - sending user.
     * @return self
     */
    public function setSender(User $sender): self
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * Set instance receiver.
     * @param User $receiver - receiving user.
     * @return self
     */
    public function setReceiver(User $receiver): self
    {
        $receiverAddress = $this->getWalletAddress($receiver);
        $this->receiverAddress = $receiverAddress;
        return $this;
    }
}
