<?php

namespace Spec\Minds\Core\Blockchain\Skale\Transaction;

use Exception;
use PhpSpec\ObjectBehavior;
use Minds\Core\Config;
use Minds\Core\Blockchain\Skale\Transaction\RewardsDispatcher;
use Minds\Core\Blockchain\Skale\Transaction\MultiTransaction\Manager as MultiTransactionManager;
use Minds\Core\Blockchain\Skale\Locks;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

class RewardsDispatcherSpec extends ObjectBehavior
{
    /** @var MultiTransactionManager */
    private $multiTransactionManager;

    /** @var Locks */
    private $locks;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var Config */
    private $config;

    /** @var string */
    private $rewardDistributorUserGuid = '0123012301230';

    public function let(
        MultiTransactionManager $multiTransactionManager,
        Locks $locks,
        EntitiesBuilder $entitiesBuilder,
        Config $config,
        User $rewardDistributor
    ) {
        $this->multiTransactionManager = $multiTransactionManager;
        $this->locks = $locks;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->config = $config;

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'skale' => [
                    'rewards_distributor_user_guid' => $this->rewardDistributorUserGuid
                ]
            ]);
            
        $this->entitiesBuilder->single($this->rewardDistributorUserGuid)
            ->shouldBeCalled()
            ->willReturn($rewardDistributor);

        $this->multiTransactionManager->setSender($rewardDistributor)
            ->shouldBeCalled();

        $this->beConstructedWith(
            $multiTransactionManager,
            $locks,
            $entitiesBuilder,
            $config
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RewardsDispatcher::class);
    }

    public function it_should_set_a_receiver_by_guid(User $receiver)
    {
        $receiverGuid = '1232139123123';
        
        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);
        
        $this->setReceiverByGuid($receiverGuid);
    }

    public function it_should_throw_if_setting_invalid_user_as_receiver_by_guid()
    {
        $receiverGuid = '1232139123123';
        
        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn(null);
        
        $this->shouldThrow(UserErrorException::class)->duringSetReceiverByGuid($receiverGuid);
    }

    public function it_should_lock_sender(User $sender)
    {
        $userGuid = '1238912312837';

        $sender->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->multiTransactionManager->getSender()
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->locks->lock($userGuid)
            ->shouldBeCalled();

        $this->lockSender();
    }

    public function it_should_unlock_sender(User $sender)
    {
        $userGuid = '1238912312837';

        $sender->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->multiTransactionManager->getSender()
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->locks->unlock($userGuid)
            ->shouldBeCalled();

        $this->unlockSender();
    }

    public function it_should_send(User $receiver)
    {
        $userGuid = '123i12903123123';
        $txHash = '0x000000000';
        $amountWei = '10000000000000';

        $receiver->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->setReceiver($receiver);

        $this->locks->isLocked($userGuid)
            ->shouldBeCalled()
            ->willReturn(false);
        
        $this->locks->lock($userGuid)
            ->shouldBeCalled();

        $this->multiTransactionManager->setReceiver($receiver)
            ->shouldBeCalled()
            ->willReturn($this->multiTransactionManager);

        $this->multiTransactionManager->sendTokens($amountWei)
            ->shouldBeCalled()
            ->willReturn($txHash);

        $this->locks->unlock($userGuid)
            ->shouldBeCalled();
    
        $this->send($amountWei)->shouldBe($txHash);
    }

    public function it_should_throw_exception_if_lock_failed(User $receiver)
    {
        $userGuid = '123i12903123123';
        $amountWei = '10000000000000';

        $receiver->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->setReceiver($receiver);

        $this->locks->isLocked($userGuid)
            ->shouldBeCalled()
            ->willReturn(true);
        
        $this->multiTransactionManager->sendTokens($amountWei)
            ->shouldNotBeCalled();

        $this->locks->unlock($userGuid)
            ->shouldNotBeCalled();
    
        $this->shouldThrow(LockFailedException::class)->duringSend($amountWei);
    }

    public function exception_in_multi_transaction_manager_should_still_unlock_wallet(User $receiver)
    {
        $userGuid = '123i12903123123';
        $amountWei = '10000000000000';

        $receiver->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->setReceiver($receiver);

        $this->locks->isLocked($userGuid)
            ->shouldBeCalled()
            ->willReturn(false);
        
        $this->locks->lock($userGuid)
            ->shouldBeCalled();

        $this->multiTransactionManager->setReceiver($receiver)
            ->shouldBeCalled()
            ->willReturn($this->multiTransactionManager);

        $this->multiTransactionManager->sendTokens($amountWei)
            ->shouldBeCalled()
            ->willThrow(new Exception('Exception'));

        $this->locks->unlock($userGuid)
            ->shouldBeCalled();
    
        $this->send($amountWei)->shouldBe(null);
    }
}
