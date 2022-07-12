<?php

namespace Minds\Core\Blockchain\Skale;

use Minds\Core\Blockchain\Services\Skale;
use Minds\Core\Blockchain\Skale\Keys;
use Minds\Core\Blockchain\Wallets\Skale\Balance;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Core\Blockchain\Skale\Transaction\Manager as TransactionManager;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;

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
     * @param Skale|null - skale client for RPC.
     * @param EntitiesBuilder|null - build entities by username / guid.
     * @param Config|null - configuration.
     */
    public function __construct(
        private ?Keys $keys = null,
        private ?Balance $balance = null,
        private ?TransactionManager $transactionManager = null,
        private ?Skale $skaleClient = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Config $config = null
    ) {
        $this->keys ??= Di::_()->get('Blockchain\Skale\Keys');
        $this->balance ??= Di::_()->get('Blockchain\Wallets\Skale\Balance');
        $this->transactionManager ??= Di::_()->get('Blockchain\Skale\Transaction\Manager');
        $this->skaleClient ??= Di::_()->get('Blockchain\Services\Skale');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->config ??= Di::_()->get('Config');
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
     * Will wait for transaction confirmation before returning.
     * @param User $sender - sending user.
     * @param User|null $receiver - receiving user.
     * @param string|null $receiverAddress - receiving address.
     * @param string|null $amountWei - amount in wei - if null transaction manager will send default amount.
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

        $this->waitForConfirmation($txHash);

        return $txHash;
    }

    /**
     * Send tokens to a given user or address. Either user or address must be provided (but not both).
     * Will wait for transaction confirmation before returning.
     * @param User $sender - sending user.
     * @param User|null $receiver - receiving user.
     * @param string|null $receiverAddress - receiving address.
     * @param string $amountWei - amount to send in wei.
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

        // If sender does not enough sfuel, send sfuel first.
        // Will wait for confirmation before proceeding.
        $this->checkSFuel($sender);

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

        $this->waitForConfirmation($txHash);

        return $txHash;
    }

    /**
     * Checks whether a user has enough sFuel (network equivalent of Ether),
     * based on a configured "minimum" value. If the sender does not have enough,
     * will send sFuel and wait for transaction confirmation.
     * @param User $sender - sender to check / distribute to.
     * @return string|null txhash for sending sFuel if needed, null if not.
     */
    public function checkSFuel(User $sender): ?string
    {
        if (!$this->hasEnoughSFuel($sender)) {
            $distributorGuid = $this->config->get('blockchain')['skale']['default_sfuel_distributor_guid'] ?? '100000000000000519';
            $distributor = $this->entitiesBuilder->single($distributorGuid);

            if (!$distributor || !$distributor instanceof User) {
                throw new ServerErrorException('sFuel distributor not found');
            }

            $txHash = $this->sendSFuel(
                sender: $distributor,
                receiver: $sender,
            );

            $this->waitForConfirmation($txHash);
        }
        return null;
    }

    /**
     * Whether user has enough sFuel for transactions based on a configurable threshold.
     * @param User $user - user to check.
     * @return boolean - true if user has enough sFuel based on threshold.
     */
    public function hasEnoughSFuel(User $user): bool
    {
        $lowBalanceThreshold = $this->config->get('blockchain')['skale']['sfuel_low_threshold'] ?? 8801000000;
        $balance = $this->getSFuelBalance($user);
        return $balance >= $lowBalanceThreshold;
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

    /**
     * Wait for transaction to be confirmed.
     * @param string $txHash - transaction hash to wait for.
     * @throws ServerErrorException - if transaction is reverted.
     * @throws Exception - if there is an error getting the transaction.
     * @return boolean - true if transaction is confirmed, false if there is a timeout.
     */
    public function waitForConfirmation(string $txHash): bool
    {
        $config = $this->config->get('blockchain')['skale'];
        $timeoutSeconds = $config['confirmation_timeout_seconds'] ?? 60;
        $pollingGap = $config['confirmation_polling_gap_seconds'] ?? 5;
        $timeoutAt = time() + $timeoutSeconds;

        while (time() < $timeoutAt) {
            $transaction = $this->getTransaction($txHash);

            if ($transaction && isset($transaction['revertReason']) && $transaction['revertReason']) {
                throw new ServerErrorException($transaction['revertReason']);
            }

            if ($transaction && $transaction['blockHash']) {
                return true;
            }

            sleep($pollingGap);
        }
        return false;
    }

    /**
     * Get transaction by tx hash.
     * @param string $txHash - transaction hash to get.
     * @throws Exception if there is an error getting the transaction.
     * @return array - transaction data or empty array if none found.
     */
    public function getTransaction(string $txHash): array
    {
        return $this->skaleClient->request(
            'eth_getTransactionReceipt',
            [
                $txHash
            ]
        ) ?? [];
    }
}
