<?php

namespace Minds\Core\Blockchain\Skale\Escrow;

use Minds\Core\Blockchain\Skale\Tools as SkaleTools;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

/**
 * Manages escrow between user and another party / or contract such as boost or withdraw.
 * by placing funds in a dedicated escrow user when the context is an action that should be
 * entering funds into an escrow (e.g. boost or withdraw creation), and sending FROM the escrow user when an action is one that
 * should be claiming or refunding funds FROM the escrow (e.g. withdraw rejection, boost rejection, boost acceptance).
 */
class Manager
{
    /** @var User - user either sending to or receiving from the escrow. */
    protected $user;
    
    /** @var string $context - a valid escrow context. */
    protected $context;

    /** @var string $amountWei - amount to transaction in wei. */
    protected $amountWei;

    /**
     * Construct.
     * @param EntitiesBuilder|null $entitiesBuilder - builds escrow user(s).
     * @param SkaleTools|null $skaleTools - used to send a transaction.
     * @param Config|null $config - get config values.
     */
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?SkaleTools $skaleTools = null,
        private ?Config $config = null
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->skaleTools ??= Di::_()->get('Blockchain\Skale\Tools');
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Sets a user to either by sending to or receiving from the escrow depending on context.
     * @param User $user - recipient or sender in relative to escrow user.
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Context of the escrow. This dictates which way funds move (and who to/from).
     * @param string $context
     * @return self
     */
    public function setContext(string $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Amount to be transaction in wei.
     * @param string $amountWei - amount to be transacted in wei.
     * @return self
     */
    public function setAmountWei(string $amountWei): self
    {
        $this->amountWei = $amountWei;
        return $this;
    }

    /**
     * Send a transaction to or from the correct escrow user, depending on context.
     * @return EscrowTransaction - object containing sender, receiver and tx hash.
     */
    public function send(): EscrowTransaction
    {
        $escrowTransaction = new EscrowTransaction();

        // When transacting from admin owned wallets, we must pre-supply SFuel to speed up the process of admin queues.
        $checkSFuel = true;

        switch ($this->context) {
            case 'boost_refund':
            case 'boost_charge':
                $escrowTransaction
                    ->setSender($this->entitiesBuilder->single(
                        $this->config->get('blockchain')['skale']['boost_escrow_user_guid']
                    ))
                    ->setReceiver($this->user);
                $checkSFuel = false;
                break;
            case 'boost_created':
                $escrowTransaction
                    ->setSender($this->user)
                    ->setReceiver($this->entitiesBuilder->single(
                        $this->config->get('blockchain')['skale']['boost_escrow_user_guid']
                    ));
                break;
            case 'withdraw_refund':
                $escrowTransaction
                    ->setSender($this->entitiesBuilder->single(
                        $this->config->get('blockchain')['skale']['withdrawal_escrow_user_guid']
                    ))
                    ->setReceiver($this->user);
                $checkSFuel = false;
                break;
            case 'withdraw_created':
                $escrowTransaction
                    ->setSender($this->user)
                    ->setReceiver($this->entitiesBuilder->single(
                        $this->config->get('blockchain')['skale']['withdrawal_escrow_user_guid']
                    ));
                break;
            case 'direct_credit':
                $escrowTransaction
                    ->setSender($this->entitiesBuilder->single(
                        $this->config->get('blockchain')['skale']['balance_sync_user_guid']
                    ))
                    ->setReceiver($this->user);
                $checkSFuel = false;
                break;
            default:
                throw new ServerErrorException('Context not supported');
        }

        $txHash = $this->sendSkaleTransaction($escrowTransaction, checkSFuel: $checkSFuel);
        $escrowTransaction->setTxHash($txHash);
        return $escrowTransaction;
    }

    /**
     * Sends transaction via SKALE.
     * @param EscrowTransaction $escrowTransaction - object containing sender and receiver.
     * @param bool $checkSFuel - whether SFuel should be checked.
     * @return string - txid of skale transaction.
     */
    private function sendSkaleTransaction(EscrowTransaction $escrowTransaction, bool $checkSFuel): string
    {
        return $this->skaleTools->sendTokens(
            amountWei: (string) abs($this->amountWei),
            sender: $escrowTransaction->getSender(),
            receiver: $escrowTransaction->getReceiver(),
            waitForConfirmation: false,
            checkSFuel: $checkSFuel
        );
    }
}
