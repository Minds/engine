<?php

namespace Spec\Minds\Core\Blockchain\Wallets\OffChain;

use Minds\Core\Blockchain\Transactions\Repository;
use Minds\Core\Blockchain\Wallets\OffChain\Balance;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Blockchain\Skale\Locks as SkaleLocks;
use Minds\Core\Blockchain\Skale\Tools as SkaleTools;
use Minds\Core\Blockchain\Skale\Escrow\Manager as SkaleEscrowManager;
use Minds\Core\Blockchain\Skale\Escrow\EscrowTransaction;
use Minds\Core\Config\Config;
use Minds\Core\Data\Locks\Redis as Locks;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\GuidBuilder;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TransactionsSpec extends ObjectBehavior
{
    /** @var Repository */
    private $repo;
    /** @var Balance */
    private $balance;
    /** @var Locks */
    private $locks;
    /** @var GuidBuilder */
    private $guid;
    /** @var SkaleTools */
    private $skaleTools;
    /** @var SkaleEscrowManager */
    private $skaleEscrowManager;
    /** @var SkaleLocks */
    private $skaleLocks;
    /** @var ExperimentsManager */
    private $experiments;
    /** @var Config */
    private $config;

    public function let(
        Repository $repo,
        Balance $balance,
        Locks $locks,
        GuidBuilder $guid,
        SkaleTools $skaleTools,
        SkaleEscrowManager $skaleEscrowManager,
        SkaleLocks $skaleLocks,
        ExperimentsManager $experiments,
        Config $config
    ) {
        $this->repo = $repo;
        $this->balance = $balance;
        $this->locks = $locks;
        $this->guid = $guid;
        $this->skaleTools = $skaleTools;
        $this->skaleEscrowManager = $skaleEscrowManager;
        $this->skaleLocks = $skaleLocks;
        $this->experiments = $experiments;
        $this->config = $config;

        $this->beConstructedWith(
            $repo,
            $balance,
            $locks,
            $guid,
            $skaleTools,
            $skaleEscrowManager,
            $skaleLocks,
            $experiments,
            $config
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Blockchain\Wallets\OffChain\Transactions');
    }

    public function it_should_create_a_rewards_transaction()
    {
        $user = new User;
        $user->guid = 123;
        $this->setUser($user)
            ->setAmount(5)
            ->setType('spec');

        $this->experiments->isOn('engine-2350-skale-mirror')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->locks->setKey(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->locks);
        $this->locks->setTTL(120)
            ->shouldBeCalled()
            ->willReturn($this->locks);
        $this->locks->isLocked()
            ->shouldBeCalled()
            ->willReturn(false);
        $this->locks->lock()
            ->shouldBeCalled()
            ->willReturn(null);
        $this->locks->unlock()
            ->shouldBeCalled();

        $this->balance->setUser($user)->willReturn($this->balance);
        $this->balance->get()->willReturn(10);

        $this->guid->build()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->repo->add(Argument::that(function ($transaction) use ($user) {
            return $transaction->getTx() == 'oc:123'
                && $transaction->getUserGuid() == $user->guid
                && $transaction->getWalletAddress() == 'offchain'
                && $transaction->getAmount() == 5
                && $transaction->getContract() == 'offchain:spec'
                && $transaction->isCompleted() == true
                && $transaction->isFailed() == false;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->create();
    }

    public function it_should_create_a_rewards_transaction_but_fail_to_lock()
    {
        $user = new User;
        $user->guid = 123;
        $this->setUser($user)
            ->setAmount(5)
            ->setType('spec');

        $this->experiments->isOn('engine-2350-skale-mirror')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->locks->setKey(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->locks);
        $this->locks->setTTL(120)
            ->shouldBeCalled()
            ->willReturn($this->locks);
        $this->locks->isLocked()
            ->shouldBeCalled()
            ->willReturn(false);
        $this->locks->unlock()
            ->shouldBeCalled();
        $this->locks->lock()
            ->shouldBeCalled()
            ->willThrow(new \Exception());

        $this->balance->setUser($user)->willReturn($this->balance);
        $this->balance->get()->willReturn(10);

        $this->guid->build()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->repo->add(Argument::that(function ($transaction) use ($user) {
            return $transaction->getTx() == 'oc:123'
                && $transaction->getUserGuid() == $user->guid
                && $transaction->getWalletAddress() == 'offchain'
                && $transaction->getAmount() == 5
                && $transaction->getContract() == 'offchain:spec'
                && $transaction->isCompleted() == true
                && $transaction->isFailed() == false;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->create();
    }

    public function it_should_not_create_a_rewards_transaction_if_insufficient_balance()
    {
        $user = new User;
        $user->guid = 123;
        $this->setUser($user)
            ->setAmount(5)
            ->setType('spec');

        $this->experiments->isOn('engine-2350-skale-mirror')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->locks->setKey(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->locks);
        $this->locks->setTTL(120)
            ->shouldBeCalled()
            ->willReturn($this->locks);
        $this->locks->isLocked()
            ->shouldBeCalled()
            ->willReturn(false);
        $this->locks->lock()
            ->shouldBeCalled()
            ->willReturn(null);
        $this->locks->unlock()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->balance->setUser($user)->willReturn($this->balance);
        $this->balance->get()->willReturn(-55);

        $this->shouldThrow(new \Exception('Not enough funds'))->duringCreate();
    }

    public function it_should_throw_exception_if_save_fails()
    {
        $user = new User;
        $user->guid = 123;
        $this->setUser($user)
            ->setAmount(5)
            ->setType('spec');

        $this->experiments->isOn('engine-2350-skale-mirror')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->locks->setKey(Argument::that(function ($key) use ($user) {
            return $key === "balance:{$user->guid}";
        }))
            ->shouldBeCalled()
            ->willReturn($this->locks);
        $this->locks->setTTL(120)
            ->shouldBeCalled()
            ->willReturn($this->locks);
        $this->locks->isLocked()
            ->shouldBeCalled()
            ->willReturn(false);
        $this->locks->lock()
            ->shouldBeCalled()
            ->willReturn(null);
        $this->locks->unlock()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->balance->setUser($user)->willReturn($this->balance);
        $this->balance->get()->willReturn(10);

        $this->guid->build()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->repo->add(Argument::that(function ($transaction) use ($user) {
            return $transaction->getUserGuid() == $user->guid
                && $transaction->getAmount() == 5
                && $transaction->getContract() == 'offchain:spec';
        }))
            ->shouldBeCalled()
            ->willReturn(false);

        $this->shouldThrow(new \Exception('Could not add transaction'))->duringCreate();
    }

    public function it_should_throw_exception_if_locked()
    {
        $user = new User;
        $user->guid = 123;
        $this->setUser($user)
            ->setAmount(5)
            ->setType('spec');

        $this->experiments->isOn('engine-2350-skale-mirror')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->locks->setKey(Argument::that(function ($key) use ($user) {
            return $key === "balance:{$user->guid}";
        }))
            ->shouldBeCalled()
            ->willReturn($this->locks);
        $this->locks->isLocked()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->balance->setUser($user)->willReturn($this->balance);
        $this->balance->get()->willReturn(10);

        $this->shouldThrow(new LockFailedException())->duringCreate();
    }

    public function it_should_convert_a_value_to_wei()
    {
        $this->toWei(10)->shouldReturn('10000000000000000000');
    }

    public function it_should_transfer_from_another_user(
        User $receiver,
        User $sender
    ) {
        $receiver->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $sender->get('guid')
            ->shouldBeCalled()
            ->willReturn(1001);

        $this->experiments->isOn('engine-2350-skale-mirror')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->locks->setKey('balance:1000')
            ->shouldBeCalledTimes(3)
            ->willReturn($this->locks);

        $this->locks->setKey('balance:1001')
            ->shouldBeCalledTimes(3)
            ->willReturn($this->locks);

        $this->locks->isLocked()
            ->shouldBeCalledTimes(2)
            ->willReturn(false);

        $this->locks->setTTL(120)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->locks);

        $this->locks->lock()
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this->balance->setUser($sender)
            ->shouldBeCalled()
            ->willReturn($this->balance);

        $this->balance->get()
            ->shouldBeCalled()
            ->willReturn(10000);

        $this->guid->build()
            ->shouldBeCalledTimes(2)
            ->willReturn(5000);

        $this->repo->add(Argument::that(function ($txs) {
            return $txs[0]->getUserGuid() === 1001 &&
                $txs[1]->getUserGuid() === 1000;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->locks->unlock()
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this
            ->setType('test')
            ->setUser($receiver)
            ->setAmount(8765)
            ->setData(['test' => true])
            ->transferFrom($sender);
    }

    public function it_should_fail_if_no_receiver_during_transfer_from(
        User $receiver,
        User $sender
    ) {
        $receiver->get('guid')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repo->add(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setType('test')
            ->setUser($receiver)
            ->setAmount(8765)
            ->setData(['test' => true])
            ->shouldThrow(new \Exception('Invalid receiver'))
            ->duringTransferFrom($sender);
    }

    public function it_should_fail_if_no_sender_during_transfer_from(
        User $receiver,
        User $sender
    ) {
        $receiver->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $sender->get('guid')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repo->add(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setType('test')
            ->setUser($receiver)
            ->setAmount(8765)
            ->setData(['test' => true])
            ->shouldThrow(new \Exception('Invalid sender'))
            ->duringTransferFrom($sender);
    }

    public function it_should_fail_if_sender_is_locked_during_transfer_from(
        User $receiver,
        User $sender,
        Locks $receiverLock,
        Locks $senderLock
    ) {
        $receiver->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $sender->get('guid')
            ->shouldBeCalled()
            ->willReturn(1001);

        $this->locks->setKey('balance:1000')
            ->shouldBeCalled()
            ->willReturn($receiverLock);

        $this->locks->setKey('balance:1001')
            ->shouldBeCalled()
            ->willReturn($senderLock);

        $receiverLock->isLocked()
            ->shouldBeCalled()
            ->willReturn(false);

        $senderLock->isLocked()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repo->add(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setType('test')
            ->setUser($receiver)
            ->setAmount(8765)
            ->setData(['test' => true])
            ->shouldThrow(LockFailedException::class)
            ->duringTransferFrom($sender);
    }

    public function it_should_fail_if_receiver_is_locked_during_transfer_from(
        User $receiver,
        User $sender,
        Locks $receiverLock
    ) {
        $receiver->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $sender->get('guid')
            ->shouldBeCalled()
            ->willReturn(1001);

        $this->locks->setKey('balance:1000')
            ->shouldBeCalled()
            ->willReturn($receiverLock);

        $receiverLock->isLocked()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repo->add(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setType('test')
            ->setUser($receiver)
            ->setAmount(8765)
            ->setData(['test' => true])
            ->shouldThrow(LockFailedException::class)
            ->duringTransferFrom($sender);
    }

    public function it_should_fail_if_amount_is_lesser_than_zero_during_transfer_from(
        User $receiver,
        User $sender
    ) {
        $receiver->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $sender->get('guid')
            ->shouldBeCalled()
            ->willReturn(1001);

        $this->experiments->isOn('engine-2350-skale-mirror')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->locks->setKey('balance:1000')
            ->shouldBeCalledTimes(3)
            ->willReturn($this->locks);

        $this->locks->setKey('balance:1001')
            ->shouldBeCalledTimes(3)
            ->willReturn($this->locks);

        $this->locks->isLocked()
            ->shouldBeCalledTimes(2)
            ->willReturn(false);

        $this->locks->setTTL(120)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->locks);

        $this->locks->lock()
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this->repo->add(Argument::cetera())
            ->shouldNotBeCalled();

        $this->locks->unlock()
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this
            ->setType('test')
            ->setUser($receiver)
            ->setAmount(0)
            ->setData(['test' => true])
            ->shouldThrow(new \Exception('Amount should be greater than 0'))
            ->duringTransferFrom($sender);
    }

    public function it_should_fail_if_sender_does_not_have_enough_funds_during_transfer_from(
        User $receiver,
        User $sender
    ) {
        $receiver->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $sender->get('guid')
            ->shouldBeCalled()
            ->willReturn(1001);

        $this->experiments->isOn('engine-2350-skale-mirror')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->locks->setKey('balance:1000')
            ->shouldBeCalledTimes(3)
            ->willReturn($this->locks);

        $this->locks->setKey('balance:1001')
            ->shouldBeCalledTimes(3)
            ->willReturn($this->locks);

        $this->locks->isLocked()
            ->shouldBeCalledTimes(2)
            ->willReturn(false);

        $this->locks->setTTL(120)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->locks);

        $this->locks->lock()
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this->balance->setUser($sender)
            ->shouldBeCalled()
            ->willReturn($this->balance);

        $this->balance->get()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->repo->add(Argument::cetera())
            ->shouldNotBeCalled();

        $this->locks->unlock()
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this
            ->setType('test')
            ->setUser($receiver)
            ->setAmount(8765)
            ->setData(['test' => true])
            ->shouldThrow(new \Exception('Not enough sender funds'))
            ->duringTransferFrom($sender);
    }

    public function it_should_mirror_a_charge_to_skale(
        EscrowTransaction $escrowTransaction,
        User $receiver,
        User $sender
    ) {
        $user = new User;
        $user->guid = 123;
        $this->setUser($user)
            ->setAmount(5)
            ->setType('spec');

        $this->experiments->isOn('engine-2350-skale-mirror')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->locks->setKey(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->locks);
        $this->locks->setTTL(120)
            ->shouldBeCalled()
            ->willReturn($this->locks);
        $this->locks->isLocked()
            ->shouldBeCalled()
            ->willReturn(false);
        $this->locks->lock()
            ->shouldBeCalled()
            ->willReturn(null);
        $this->locks->unlock()
            ->shouldBeCalled();

        $this->skaleLocks->isLocked(123)
            ->shouldBeCalled()
            ->willReturn(false);
        $this->skaleLocks->lock(123)
            ->shouldBeCalled()
            ->willReturn(false);
        $this->skaleLocks->unlock(123)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->balance->setUser($user)->willReturn($this->balance);
        $this->balance->get()->willReturn(10);

        $this->guid->build()
            ->shouldBeCalled()
            ->willReturn('123');


        $escrowTransaction->getReceiver()
            ->shouldBeCalled()
            ->willReturn($receiver);

        $escrowTransaction->getSender()
            ->shouldBeCalled()
            ->willReturn($sender);

        $escrowTransaction->getTxHash()
            ->shouldBeCalled()
            ->willReturn('0x00');
        
        $context = 'context';

        $this->setData(['context' => $context])
            ->setAmount(5);
        
        $this->skaleEscrowManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->skaleEscrowManager);
    
        $this->skaleEscrowManager->setContext($context)
            ->shouldBeCalled()
            ->willReturn($this->skaleEscrowManager);
        
        $this->skaleEscrowManager->setAmountWei(5)
            ->shouldBeCalled()
            ->willReturn($this->skaleEscrowManager);
    
        $this->skaleEscrowManager->send()
            ->shouldBeCalled()
            ->willReturn($escrowTransaction);

        $this->repo->add(Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->create();
    }

    public function it_should_mirror_transfer_from_to_another_user_on_skale(
        User $receiver,
        User $sender
    ) {
        $receiver->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $sender->get('guid')
            ->shouldBeCalled()
            ->willReturn(1001);

        $this->experiments->isOn('engine-2350-skale-mirror')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->locks->setKey('balance:1000')
            ->shouldBeCalledTimes(3)
            ->willReturn($this->locks);

        $this->locks->setKey('balance:1001')
            ->shouldBeCalledTimes(3)
            ->willReturn($this->locks);

        $this->locks->isLocked()
            ->shouldBeCalledTimes(2)
            ->willReturn(false);

        $this->locks->setTTL(120)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->locks);

        $this->locks->lock()
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this->skaleLocks->isLocked(1000)
            ->shouldBeCalled()
            ->willReturn(false);
        $this->skaleLocks->lock(1000)
            ->shouldBeCalled()
            ->willReturn(false);
        $this->skaleLocks->unlock(1000)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->skaleLocks->isLocked(1001)
            ->shouldBeCalled()
            ->willReturn(false);
        $this->skaleLocks->lock(1001)
            ->shouldBeCalled()
            ->willReturn(false);
        $this->skaleLocks->unlock(1001)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->balance->setUser($sender)
            ->shouldBeCalled()
            ->willReturn($this->balance);

        $this->balance->get()
            ->shouldBeCalled()
            ->willReturn(10000);

        $this->guid->build()
            ->shouldBeCalledTimes(2)
            ->willReturn(5000);

        $this->skaleTools->sendTokens(
            amountWei: 8765,
            sender: $sender,
            receiver: $receiver,
            receiverAddress: null,
            waitForConfirmation: true,
            checkSFuel: true
        )->shouldBeCalled();

        $this->repo->add(Argument::that(function ($txs) {
            return $txs[0]->getUserGuid() === 1001 &&
                $txs[1]->getUserGuid() === 1000;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->locks->unlock()
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this
            ->setType('test')
            ->setUser($receiver)
            ->setAmount(8765)
            ->setData(['test' => true])
            ->transferFrom($sender);
    }
}
