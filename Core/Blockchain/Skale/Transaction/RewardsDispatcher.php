<?php

namespace Minds\Core\Blockchain\Skale\Transaction;

use Exception;
use Minds\Core\Blockchain\Skale\Locks;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Core\Blockchain\Skale\Transaction\MultiTransaction\Manager as MultiTransactionManager;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Exceptions\UserErrorException;

/**
 * Dispatch rewards through multi-transaction manager. All transactions will be sent
 * to the user referenced by configs `rewards_distributor_user_guid`. Nonce incrementation
 * should be automatically handled at a lower level. WILL NOT handle offchain mirroring.
 */
class RewardsDispatcher
{
    /** @var User - receiving user */
    private User $receiver;

    /**
     * Constructor.
     * @param MultiTransactionManager|null $multiTransactionManager - used to send transactions.
     * @param Locks|null $entitiesBuilder - used to lock wallet so other transactions cannot be made.
     * @param EntitiesBuilder|null $entitiesBuilder - build entities.
     * @param Config|null $config - config.
     */
    public function __construct(
        private ?MultiTransactionManager $multiTransactionManager = null,
        private ?Locks $locks = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Config $config = null
    ) {
        $this->multiTransactionManager ??= new MultiTransactionManager();
        $this->locks ??= Di::_()->get('Blockchain\Skale\Locks');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->config ??= Di::_()->get('Config');

        $rewardsDistributor = $this->entitiesBuilder->single(
            $this->config->get('blockchain')['skale']['rewards_distributor_user_guid'] ?? false
        ) ?? false;

        if (!$rewardsDistributor) {
            throw new ServerErrorException('Incorrectly configured rewards distributor user');
        }

        $this->multiTransactionManager->setSender($rewardsDistributor);
    }

    /**
     * Set receiver by guid
     * @param string $receiverGuid - receivers guid.
     * @return self
     */
    public function setReceiverByGuid(string $receiverGuid): self
    {
        $receiver = $this->entitiesBuilder->single($receiverGuid);

        if (!$receiver || !($receiver instanceof User)) {
            throw new UserErrorException('Attempted to dispatch SKALE rewards to an invalid receiver');
        }

        $this->receiver = $receiver;
        return $this;
    }

    /**
     * Set receiver.
     * @param User $receiver - receivers.
     * @return self
     */
    public function setReceiver(User $receiver): self
    {
        $this->receiver = $receiver;
        return $this;
    }

    /**
     * Lock the senders wallet - should be done before calling any
     * send operations to ensure you do not run into any nonce conflicts.
     * Make sure to call `unlockSender` when you are done.
     *
     * TTL can be configured via `rewards_wallet_lock_ttl`.
     * @return void
     */
    public function lockSender(): void
    {
        $ttl = $this->config->get('blockchain')['skale']['rewards_wallet_lock_ttl'] ?? 43200;  // 12 hours default.
        $this->locks->lock($this->multiTransactionManager->getSender()->getGuid(), $ttl);
    }

    /**
     * Unlock the senders wallet - should only be done AFTER calling all send
     * operations..
     * @return void
     */
    public function unlockSender(): void
    {
        $this->locks->unlock($this->multiTransactionManager->getSender()->getGuid());
    }

    /**
     * Send tokens.
     * @param string $amountWei - amount to send in wei.
     * @throws LockFailedException - if lock failed because there is a lock applied to the wallet already.
     * @return string|null - transaction hash.
     */
    public function send(string $amountWei): ?string
    {
        $receiverGuid = $this->receiver->getGuid();

        if ($this->locks->isLocked($receiverGuid)) {
            throw new LockFailedException();
        }

        $this->locks->lock($receiverGuid);

        try {
            return $this->multiTransactionManager
                ->setReceiver($this->receiver)
                ->sendTokens($amountWei);
        } finally {
            $this->locks->unlock($receiverGuid);
        }
    }
}
