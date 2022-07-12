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
    /** @var string default amount of sFuel to be distributed. - configurable in constructor */
    private string $defaultDistributionAmountWei = '220000000000';

    /** @var int gas limit of standard transfer. - configurable in constructor */
    private int $transferGasLimit = 51280;

    /** @var string|null address of MINDS token on SKALE network - configurable in constructor */
    private ?string $tokenAddress = null;
    
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
        $this->keys ??= Di::_()->get('Blockchain\Skale\Keys');
        $this->config ??= Di::_()->get('Config');

        if ($defaultDistributionAmountWei = $this->config->get('blockchain')['skale']['default_sfuel_distribution_amount_wei'] ?? false) {
            $this->defaultDistributionAmountWei = $defaultDistributionAmountWei;
        }

        if ($transferGasLimit = $this->config->get('blockchain')['skale']['transfer_gas_limit'] ?? false) {
            $this->transferGasLimit = $transferGasLimit;
        }

        if ($tokenAddress = $this->config->get('blockchain')['skale']['minds_token_address'] ?? false) {
            $this->tokenAddress = $tokenAddress;
        } else {
            throw new ServerErrorException('No SKALE Minds token address set in config');
        }
    }

    /**
     * Construct new instance with passed in users.
     * Must pass in a sender, and either a receiver OR receiver address.
     * @param User $sender - sending user
     * @param User|null $receiver - receiving user.
     * @param string|null $receiverAddress - receiving address.
     * @return self - new instance.
     */
    public function withUsers(
        User $sender,
        ?User $receiver = null,
        ?string $receiverAddress = null
    ): self {
        if (!($receiver xor $receiverAddress)) {
            throw new ServerErrorException('Must set ONE of receiverAddress and receiver');
        }

        if ($receiver) {
            $receiverAddress = $this->getWalletAddress($receiver);
        }

        $instance = clone $this;
        $instance->sender = $sender;
        $instance->receiverAddress = $receiverAddress;
        return $instance;
    }

    /**
     * Gets instance sender.
     * @return ?User sender.
     */
    public function getSender(): ?User
    {
        return $this->sender;
    }

    /**
     * Gets instance receiver address.
     * @return ?string receiver address.
     */
    public function getReceiverAddress(): ?string
    {
        return $this->receiverAddress;
    }

    /**
     * Send MINDS tokens on SKALE network from sender to receiver.
     * @param string $amountWei - amount to send in wei.
     * @throws ServerErrorException - on error.
     * @return ?string - tx hash.
     */
    public function sendTokens(string $amountWei = null): ?string
    {
        if (!$amountWei || !$this->receiverAddress || !$this->sender) {
            throw new ServerErrorException('Cannot send MINDS on SKALE with null sender or receiver');
        }

        $privateKey = $this->getPrivateKey($this->sender);

        try {
            return $this->skaleClient->sendRawTransaction($privateKey, [
                'from' => $this->getWalletAddress($this->sender),
                'to' => $this->tokenAddress,
                'gasLimit' => BigNumber::_($this->transferGasLimit)->toHex(true),
                'data' => $this->skaleClient->encodeContractMethod('transfer(address,uint256)', [
                    $this->receiverAddress,
                    BigNumber::_($amountWei)->toHex(true),
                ])
            ]);
        } catch (\Exception $e) {
            throw new ServerErrorException(
                $e->getMessage() .
                ' ' .
                $this->sender->getUsername() ?? 'unknown' .
                ' => ' .
                $this->receiverAddress
            );
        }
    }

    /**
     * Send sFuel from sender to receiver.
     * @param string|null $amountWei - amount to send in wei - will default to default distribution amount if not provided.
     * @throws ServerErrorException - on error.
     * @return string|null - tx hash.
     */
    public function sendSFuel(?string $amountWei = null): ?string
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
