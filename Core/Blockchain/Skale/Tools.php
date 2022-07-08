<?php

namespace Minds\Core\Blockchain\Skale;

use Minds\Core\Blockchain\Skale\Keys;
use Minds\Core\Blockchain\Wallets\Skale\Balance;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Core\Blockchain\Skale\Transaction\Manager as TransactionManager;

/**
 * Tools for SKALE network to get balances and send transactions.
 */
class Tools
{
    /**
     * Constructor.
     * @param Keys|null $keys - skale keys.
     * @param Balance|null $balance - used to get balances.
     * @param TransactionManager|null $transactionManager - used to send transactions.
     */
    public function __construct(
        private ?Keys $keys = null,
        private ?Balance $balance = null,
        private ?TransactionManager $transactionManager = null
    ) {
        $this->keys ??= Di::_()->get('Blockchain\Skale\Keys');
        $this->balance ??= Di::_()->get('Blockchain\Wallets\Skale\Balance');
        $this->transactionManager ??= Di::_()->get('Blockchain\Skale\Transaction\Manager');
    }

    /**
     * Get token balance in Wei. Either a user or address must be provided (but not both).
     * @param User|null $user - user to get balance for.
     * @param string|null $address - address to get balance for.
     * @param bool $useCache - whether cache should be used (defaults to true).
     * @throws ServerErrorException|Exception - on error.
     * @return ?string - balance in wei.
     */
    public function getTokenBalance(
        ?User $user = null,
        ?string $address = null,
        bool $useCache = true
    ): ?string {
        if (!($user xor $address)) {
            throw new ServerErrorException('Must provide user or address, but not both');
        }
        
        if ($user) {
            $address = $this->getCustodialWalletAddress($user);
        }

        return $this->balance->getTokenBalance(
            address: $address,
            useCache: $useCache
        );
    }

    /**
     * Get sFuel balance in Wei. Either a user or address must be provided (but not both).
     * @param User|null $user - user to get balance for.
     * @param string|null $address - address to get balance for.
     * @param bool $useCache - whether cache should be used (defaults to true).
     * @throws ServerErrorException|Exception - on error.
     * @return ?string - balance in wei.
     */
    public function getSFuelBalance(
        ?User $user = null,
        ?string $address = null,
        bool $useCache = true
    ): ?string {
        if (!($user xor $address)) {
            throw new ServerErrorException('Must provide user or address, but not both');
        }
        
        if ($user) {
            $address = $this->getCustodialWalletAddress($user);
        }

        return $this->balance->getSFuelBalance(
            address: $address,
            useCache: $useCache
        );
    }

    /**
     * Send SFuel to a given user or address. Either user or address must be provided (but not both).
     * @param User - sending user.
     * @param User|null - receiving user.
     * @param string|null - receiving address.
     * @param string|null - amount in wei - if null transaction manager will send default amount.
     * @throws ServerErrorException|Exception - on error.
     * @return string|null - transaction hash.
     */
    public function sendSFuel(
        User $sender,
        ?User $receiver = null,
        ?string $receiverAddress = null,
        ?string $amountWei = null
    ): ?string {
        if (!($receiver xor $receiverAddress)) {
            throw new ServerErrorException('Must provide receiver or receiver address, but not both');
        }

        $txHash = null;
        if ($receiverAddress) {
            $txHash = $this->transactionManager->withUsers(
                sender: $sender,
                receiverAddress: $receiverAddress
            )->sendSFuel($amountWei);
        } else {
            $txHash = $this->transactionManager->withUsers(
                sender: $sender,
                receiver: $receiver
            )->sendSFuel($amountWei);
        }
        return $txHash;
    }

    /**
     * Send tokens to a given user or address. Either user or address must be provided (but not both).
     * @param User - sending user.
     * @param User|null - receiving user.
     * @param string|null - receiving address.
     * @param string - amount to send in wei.
     * @throws ServerErrorException|Exception - on error.
     * @return string|null - transaction hash.
     */
    public function sendTokens(
        User $sender,
        ?User $receiver = null,
        ?string $receiverAddress = null,
        string $amountWei
    ): ?string {
        if (!($receiver xor $receiverAddress)) {
            throw new ServerErrorException('Must provide receiver or receiver address, but not both');
        }

        $txHash = null;
        if ($receiverAddress) {
            $txHash = $this->transactionManager->withUsers(
                sender: $sender,
                receiverAddress: $receiverAddress
            )->sendTokens($amountWei);
        } else {
            $txHash = $this->transactionManager->withUsers(
                sender: $sender,
                receiver: $receiver
            )->sendTokens($amountWei);
        }

        return $txHash;
    }

    /**
     * Gets custodial SKALE wallet address by username.
     * @param User $user - user to get wallet address for.
     * @return string|null - SKALE wallet address for a user.
     */
    public function getCustodialWalletAddress(User $user): ?string
    {
        return $this->keys
            ->withUser($user)
            ->getWalletAddress();
    }
}
