<?php

namespace Spec\Minds\Core\Blockchain\Skale\EventStreams;

use Minds\Core\Blockchain\EventStreams\BlockchainTransactionEvent;
use Minds\Core\Blockchain\EventStreams\BlockchainTransactionsTopic;
use Minds\Core\Blockchain\Skale\BalanceSynchronizer\AdjustmentResult;
use PhpSpec\ObjectBehavior;
use Minds\Core\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Blockchain\Skale\BalanceSynchronizer\BalanceSynchronizer;
use Minds\Core\Blockchain\Skale\BalanceSynchronizer\SyncExcludedUserException;
use Minds\Core\Blockchain\Skale\EventStreams\SkaleBalanceCheckEventStreamsSubscription;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Log\Logger;
use Minds\Entities\User;

class SkaleBalanceCheckEventStreamsSubscriptionSpec extends ObjectBehavior
{
    /** @var BalanceSynchronizer */
    private $balanceSynchronizer;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var ExperimentsManager */
    private $experiments;

    /** @var Logger */
    private $logger;

    /** @var Config */
    private $config;

    public function let(
        BalanceSynchronizer $balanceSynchronizer,
        EntitiesBuilder $entitiesBuilder,
        ExperimentsManager $experiments,
        Logger $logger,
        Config $config
    ) {
        $this->balanceSynchronizer = $balanceSynchronizer;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->experiments = $experiments;
        $this->logger = $logger;
        $this->config = $config;
        
        $this->beConstructedWith(
            $balanceSynchronizer,
            $entitiesBuilder,
            $experiments,
            $logger,
            $config
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SkaleBalanceCheckEventStreamsSubscription::class);
    }

    public function it_should_get_subscription_id()
    {
        $this->getSubscriptionId()->shouldBe('skale_balance_checks');
    }

    public function it_should_get_topic()
    {
        $this->getTopic()->shouldHaveType(BlockchainTransactionsTopic::class);
    }

    public function it_should_get_topic_regex()
    {
        $this->getTopicRegex()->shouldBe('.*');
    }

    public function it_should_consume_a_valid_event_with_no_adjustment_required(User $user)
    {
        $this->setAlreadyChecked([]);

        $senderGuid = '012345';
        $receiverGuid = '98765';
        $transactionId = '0x1111111';
        $amountWei ='100000000000';
        $walletAddress = '0x2222222';
        $contract = 'offchain:wire';

        $event = new BlockchainTransactionEvent();
        $event->setSenderGuid($senderGuid)
            ->setReceiverGuid($receiverGuid)
            ->setTransactionId($transactionId)
            ->setWalletAddress($walletAddress)
            ->setContract($contract)
            ->setAmountWei($amountWei);

        $this->experiments->isOn('engine-2360-skale-balance-sync')
            ->shouldBeCalled()
            ->willReturn(true);
        
        $this->entitiesBuilder->single($senderGuid)
            ->shouldBeCalled()
            ->willReturn($user);
            
        $this->balanceSynchronizer->withUser($user)
            ->shouldBeCalled()
            ->willReturn($this->balanceSynchronizer);
        
        $this->balanceSynchronizer->sync(dryRun: true)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_consume_a_valid_event_and_print_adjustment_requirement(
        User $user,
        AdjustmentResult $adjustmentResult
    ) {
        $this->setAlreadyChecked([]);

        $senderGuid = '012345';
        $receiverGuid = '98765';
        $transactionId = '0x1111111';
        $amountWei ='100000000000';
        $walletAddress = '0x2222222';
        $contract = 'offchain:wire';
        $adjustmentResultStringified = 'adjustment result string';

        $event = new BlockchainTransactionEvent();
        $event->setSenderGuid($senderGuid)
            ->setReceiverGuid($receiverGuid)
            ->setTransactionId($transactionId)
            ->setWalletAddress($walletAddress)
            ->setContract($contract)
            ->setAmountWei($amountWei);

        $this->experiments->isOn('engine-2360-skale-balance-sync')
            ->shouldBeCalled()
            ->willReturn(true);
        
        $this->entitiesBuilder->single($senderGuid)
            ->shouldBeCalled()
            ->willReturn($user);
            
        $this->balanceSynchronizer->withUser($user)
            ->shouldBeCalled()
            ->willReturn($this->balanceSynchronizer);

        $adjustmentResult->__toString()
            ->shouldBeCalled()
            ->willReturn($adjustmentResultStringified);

        $this->balanceSynchronizer->sync(dryRun: true)
            ->shouldBeCalled()
            ->willReturn($adjustmentResult);
            
        $this->logger->error($adjustmentResultStringified)
            ->shouldBeCalled();

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_NOT_consume_an_event_of_an_unsupported_type(
        EventInterface $invalidEvent
    ) {
        $this->consume($invalidEvent)->shouldBe(false);
    }

    public function it_should_NOT_process_balance_for_an_event_with_a_non_existent_or_invalid_user(
        User $user
    ) {
        $this->setAlreadyChecked([]);

        $senderGuid = '012345';
        $receiverGuid = '98765';
        $transactionId = '0x1111111';
        $amountWei ='100000000000';
        $walletAddress = '0x2222222';
        $contract = 'offchain:wire';

        $event = new BlockchainTransactionEvent();
        $event->setSenderGuid($senderGuid)
            ->setReceiverGuid($receiverGuid)
            ->setTransactionId($transactionId)
            ->setWalletAddress($walletAddress)
            ->setContract($contract)
            ->setAmountWei($amountWei);

        $this->experiments->isOn('engine-2360-skale-balance-sync')
            ->shouldBeCalled()
            ->willReturn(true);
        
        $this->entitiesBuilder->single($senderGuid)
            ->shouldBeCalled()
            ->willReturn(null);
            
        $this->balanceSynchronizer->withUser($user)
            ->shouldNotBeCalled();

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_NOT_process_balance_for_an_event_with_a_recently_checked_user(
        User $user
    ) {
        $this->setAlreadyChecked([]);

        $senderGuid = '012345';
        $receiverGuid = '98765';
        $transactionId = '0x1111111';
        $amountWei ='100000000000';
        $walletAddress = '0x2222222';
        $contract = 'offchain:wire';

        $event = new BlockchainTransactionEvent();
        $event->setSenderGuid($senderGuid)
            ->setReceiverGuid($receiverGuid)
            ->setTransactionId($transactionId)
            ->setWalletAddress($walletAddress)
            ->setContract($contract)
            ->setAmountWei($amountWei);

        $this->experiments->isOn('engine-2360-skale-balance-sync')
            ->shouldBeCalledTimes(2)
            ->willReturn(true);
        
        $this->entitiesBuilder->single($senderGuid)
            ->shouldBeCalledTimes(2)
            ->willReturn($user);
            
        $this->balanceSynchronizer->withUser($user)
            ->shouldBeCalledTimes(1) // not called second time.
            ->willReturn($this->balanceSynchronizer);
        
        $this->balanceSynchronizer->sync(dryRun: true)
            ->shouldBeCalledTimes(1) // not called second time.
            ->willReturn(null);

        $this->consume($event)->shouldBe(true);

        // second call doesn't trigger balance synchronizer calls.
        $this->consume($event)->shouldBe(true);
    }

    public function it_should_return_true_for_sync_excluded_users_without_dry_running_to_check_for_adjustments(
        User $user
    ) {
        $this->setAlreadyChecked([]);

        $senderGuid = '012345';
        $receiverGuid = '98765';
        $transactionId = '0x1111111';
        $amountWei ='100000000000';
        $walletAddress = '0x2222222';
        $contract = 'offchain:wire';

        $event = new BlockchainTransactionEvent();
        $event->setSenderGuid($senderGuid)
            ->setReceiverGuid($receiverGuid)
            ->setTransactionId($transactionId)
            ->setWalletAddress($walletAddress)
            ->setContract($contract)
            ->setAmountWei($amountWei);

        $this->experiments->isOn('engine-2360-skale-balance-sync')
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        
        $this->entitiesBuilder->single($senderGuid)
            ->shouldBeCalledTimes(1)
            ->willReturn($user);
            
        $this->balanceSynchronizer->withUser($user)
            ->shouldBeCalledTimes(1)
            ->willReturn($this->balanceSynchronizer);
        
        $this->balanceSynchronizer->sync(dryRun: true)
            ->shouldBeCalledTimes(1)
            ->willThrow(new SyncExcludedUserException());

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_NOT_process_event_if_feat_is_off()
    {
        $senderGuid = '012345';
        $receiverGuid = '98765';
        $transactionId = '0x1111111';
        $amountWei ='100000000000';
        $walletAddress = '0x2222222';
        $contract = 'offchain:wire';

        $event = new BlockchainTransactionEvent();
        $event->setSenderGuid($senderGuid)
            ->setReceiverGuid($receiverGuid)
            ->setTransactionId($transactionId)
            ->setWalletAddress($walletAddress)
            ->setContract($contract)
            ->setAmountWei($amountWei);

        $this->experiments->isOn('engine-2360-skale-balance-sync')
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        
        $this->entitiesBuilder->single($senderGuid)
            ->shouldNotBeCalled();
        
        $this->consume($event)->shouldBe(false);
    }
}
