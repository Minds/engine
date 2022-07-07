<?php

namespace Minds\Core\Blockchain\Skale\Transaction;

use Minds\Core\Util\BigNumber;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Core\Blockchain\Services\Skale;
use Minds\Core\Blockchain\Skale\Keys;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

/**
 * SKALE Transaction manager. Allows transactions to be send
 * between wallets on the MINDS SKALE network.
 */
class Manager
{
    /** @var int amount of sFuel to be distributed. - configurable in constructor */
    private int $defaultDistributionAmountWei = 220000000000;

    /** @var User|null sender of the transaction */
    private ?User $sender = null;

    /** @var string|null address on the receiving end of transaction */
    private ?string $receiverAddress = null;
    
    /**
     * Constructor.
     * @param Skale|null $skaleClient - client for communication with skale network.
     * @param Keys|null $keys - SKALE keys for custodial wallets.
     * @param Config|null $config - global config.
     */
    public function __construct(
        private ?Skale $skaleClient = null,
        private ?Keys $keys = null,
        private ?Config $config = null
    ) {
        $this->skaleClient ??= Di::_()->get('Blockchain\Services\Skale');
        $this->keys ??= new Keys();
        $this->config ??= Di::_()->get('Config');

        if ($defaultDistributionAmountWei = $this->config->get('blockchain')['skale']['default_sfuel_distribution_amount_wei'] ?? false) {
            $this->defaultDistributionAmountWei = $defaultDistributionAmountWei;
        }
    }

    /**
     * Set sender user
     * @param User $sender - user to set as sender.
     * @return self
     */
    public function setSender(User $sender): self
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * Set receiver address directly - allows for sending outside of custodial wallets.
     * @param string $address - receiver address.
     * @return self
     */
    public function setReceiverAddress(string $address): self
    {
        $this->receiverAddress = $address;
        return $this;
    }

    /**
     * Set receiver - if you don't know the receiving address, you
     * can pass a user object and $receiverAddress will be set accordingly.
     * @param User $receiver - receiving user.
     * @return self
     */
    public function setReceiver(User $receiver): self
    {
        return $this->setReceiverAddress(
            $this->getWalletAddress($receiver)
        );
    }

    /**
     * TODO: Build send token functionality.
     * @param int $amountWei - amount to send in wei.
     * @return void
     */
    public function sendTokens(int $amountWei = null)
    {
        // NoOp
    }

    /**
     * Send fuel from receiver to sender.
     * @param int|null $amountWei - amount to send in wei - will default to default distribution amount if not provided.
     * @throws ServerErrorException - on error.
     * @return string|null - tx hash.
     */
    public function sendSFuel(?int $amountWei = null): ?string
    {
        if (!$this->receiverAddress || !$this->sender) {
            throw new ServerErrorException('Cannot send sFuel with null sender or receiver');
        }

        $privateKey = $this->getPrivateKey($this->sender);

        try {
            return $this->skaleClient->sendRawTransaction($privateKey, [
                'from' => $this->getWalletAddress($this->sender),
                'to' => $this->receiverAddress,
                'gasLimit' => BigNumber::_(21000)->toHex(true),
                'value' => $amountWei ?? $this->defaultDistributionAmountWei
            ]);
        } catch (\Exception $e) {
            throw new ServerErrorException(
                $e->getMessage() .
                ' ' .
                $this->sender->getUsername() .
                ' => ' .
                $this->receiverAddress
            );
        }
    }

    /**
     * Gets private key for a given custodial wallet.
     * @param User $user - user to get custodial wallet for.
     * @return string|null - custodial wallet private key.
     */
    private function getPrivateKey(User $user): ?string
    {
        return $this->keys->withUser($user)->getSecp256k1PrivateKeyAsHex();
    }

    /**
     * Gets wallet address for a custodial wallet.
     * @param User $user - user to get custodial wallet for.
     * @return string|null - custodial wallet address.
     */
    private function getWalletAddress(User $user): ?string
    {
        return $this->keys->withUser($user)->getWalletAddress();
    }
}
