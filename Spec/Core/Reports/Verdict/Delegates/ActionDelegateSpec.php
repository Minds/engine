<?php

namespace Spec\Minds\Core\Reports\Verdict\Delegates;

use Minds\Core\Boost\V3\Enums\BoostRejectionReason;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Reports\Verdict\Delegates\ActionDelegate;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Reports\Verdict\Verdict;
use Minds\Core\Reports\Verdict\Delegates\EmailDelegate;
use Minds\Core\Reports\Report;
use Minds\Core\Reports\Actions;
use Minds\Core\Reports\Strikes\Manager as StrikesManager;
use Minds\Entities\Entity;
use Minds\Entities\Activity;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\Channels\Ban;
use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Log\Logger;
use Minds\Core\Monetization\Demonetization\DemonetizationContext;
use Minds\Core\Monetization\Demonetization\Strategies\DemonetizePlusUserStrategy;
use Minds\Core\Monetization\Demonetization\Strategies\DemonetizePostStrategy;
use Minds\Core\Monetization\Demonetization\Strategies\Interfaces\DemonetizableEntityInterface;
use Minds\Core\Security\Password;
use Minds\Core\Sessions;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ActionDelegateSpec extends ObjectBehavior
{
    private $entitiesBuilder;
    private $actions;
    private $strikesManager;
    private $saveAction;
    private $emailDelegate;
    private Collaborator $channelsBanManager;
    private Collaborator $demonetizationContext;
    private Collaborator $demonetizePostStrategy;
    private Collaborator $demonetizePlusUserStrategy;
    private Collaborator $boostManager;
    private Collaborator $logger;

    /** @var Sessions\CommonSessions\Manager */
    protected $commonSessionsManager;

    /** @var Password */
    protected $password;

    public function let(
        EntitiesBuilder $entitiesBuilder,
        Actions $actions,
        StrikesManager $strikesManager,
        SaveAction $saveAction,
        EmailDelegate $emailDelegate,
        Ban $channelsBanManager,
        Sessions\CommonSessions\Manager $commonSessionsManager,
        Password $password,
        DemonetizationContext $demonetizationContext,
        DemonetizePostStrategy $demonetizePostStrategy,
        DemonetizePlusUserStrategy $demonetizePlusUserStrategy,
        BoostManager $boostManager,
        Logger $logger
    ) {
        $this->beConstructedWith(
            $entitiesBuilder,
            $actions,
            null,
            $strikesManager,
            $saveAction,
            $emailDelegate,
            $channelsBanManager,
            null,
            $commonSessionsManager,
            $password,
            $demonetizationContext,
            $demonetizePostStrategy,
            $demonetizePlusUserStrategy,
            $boostManager,
            $logger
        );
        $this->entitiesBuilder = $entitiesBuilder;
        $this->actions = $actions;
        $this->strikesManager = $strikesManager;
        $this->saveAction = $saveAction;
        $this->emailDelegate = $emailDelegate;
        $this->channelsBanManager = $channelsBanManager;
        $this->commonSessionsManager = $commonSessionsManager;
        $this->password = $password;
        $this->demonetizationContext = $demonetizationContext;
        $this->demonetizePostStrategy = $demonetizePostStrategy;
        $this->demonetizePlusUserStrategy = $demonetizePlusUserStrategy;
        $this->boostManager = $boostManager;
        $this->logger = $logger;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ActionDelegate::class);
    }

    public function it_should_apply_nsfw_flags(Entity $entity)
    {
        $report = new Report;
        $report->setEntityUrn('urn:activity:123')
            ->setReasonCode(2)
            ->setSubReasonCode(1);

        $verdict = new Verdict;
        $verdict->setReport($report)
            ->setUphold(true);

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn($entity);

        $entity->getNsfw()
            ->shouldBeCalled()
            ->willReturn([ 2 ]);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $entity->setNsfw([ 1, 2 ])
            ->shouldBeCalled();

        $entity->getNsfwLock()
            ->shouldBeCalled()
            ->willReturn([ ]);

        $entity->setNsfwLock([ 1 ])
            ->shouldBeCalled();

        $this->saveAction->setEntity($entity)
            ->willReturn($this->saveAction);
        
        $this->saveAction->save()
            ->shouldBeCalled();

        $this->strikesManager->countStrikesInTimeWindow(Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn(0);

        $this->strikesManager->add(Argument::any())
            ->shouldBeCalled();

        $this->boostManager->forceRejectByEntityGuid(
            entityGuid: '123',
            reason: BoostRejectionReason::REPORT_UPHELD,
            statuses: [BoostStatus::APPROVED, BoostStatus::PENDING]
        )->shouldBeCalled()
            ->willReturn(true);

        $this->onAction($verdict);
    }

    public function it_should_removed_if_illegal(Entity $entity, Entity $user)
    {
        $report = new Report;
        $report->setEntityUrn('urn:activity:123')
            ->setEntityOwnerGuid(456)
            ->setReasonCode(1)
            ->setSubReasonCode(1);

        $verdict = new Verdict;
        $verdict->setReport($report)
            ->setUphold(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $entity->get('type')
            ->shouldBeCalled()
            ->willReturn('activity');

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->entitiesBuilder->single(456)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->channelsBanManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->channelsBanManager);
        $this->channelsBanManager->ban('1.1')
            ->shouldBeCalled();

        $this->actions->setDeletedFlag($entity, true)
            ->shouldBeCalled();

        $this->saveAction->setEntity($entity)
            ->willReturn($this->saveAction);
        
        $this->saveAction->save()
            ->shouldBeCalled();

        $this->boostManager->forceRejectByEntityGuid(
            entityGuid: '123',
            reason: BoostRejectionReason::REPORT_UPHELD,
            statuses: [BoostStatus::APPROVED, BoostStatus::PENDING]
        )->shouldBeCalled()
            ->willReturn(true);
    
        $this->onAction($verdict);
    }

    public function it_should_removed_if_spam(Entity $entity)
    {
        $report = new Report;
        $report->setEntityUrn('urn:activity:123')
            ->setReasonCode(4);

        $verdict = new Verdict;
        $verdict->setReport($report)
            ->setUphold(true)
            ->setAction('4');

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $entity->get('type')
            ->shouldBeCalled()
            ->willReturn('activity');

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->actions->setDeletedFlag(Argument::type(Entity::class), true)
            ->shouldBeCalled();

        $this->saveAction->setEntity($entity)
            ->willReturn($this->saveAction);
        
        $this->saveAction->save()
            ->shouldBeCalled();

        $this->boostManager->forceRejectByEntityGuid(
            entityGuid: '123',
            reason: BoostRejectionReason::REPORT_UPHELD,
            statuses: [BoostStatus::APPROVED, BoostStatus::PENDING]
        )->shouldBeCalled()
            ->willReturn(true);

        $this->onAction($verdict);
    }

    public function it_should_remove_if_minds_plus_and_nsfw(Activity $entity, Entity $user)
    {
        $report = new Report;
        $report->setEntityUrn('urn:activity:123')
            ->setEntityOwnerGuid(456)
            ->setReasonCode(2)
            ->setSubReasonCode(1);

        $verdict = new Verdict;
        $verdict->setReport($report)
            ->setUphold(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn($entity);

        $entity->getNsfw()
            ->willReturn([]);

        $entity->getNsfwLock()
            ->willReturn([]);

        $entity->isPayWall()
            ->willReturn(true);

        $entity->getWireThreshold()
            ->willReturn([
                'support_tier' => [
                    'urn' => 'plus_support_tier_urn'
                ]
            ]);

        $entity->setNsfw([1])
            ->willReturn($entity);
        $entity->setNsfwLock([1])
            ->willReturn($entity);

        $this->actions->setDeletedFlag($entity, true)
            ->shouldBeCalled();

        $this->saveAction->setEntity($entity)
            ->willReturn($this->saveAction);
        
        $this->saveAction->save()
            ->shouldBeCalled();

        $this->boostManager->forceRejectByEntityGuid(
            entityGuid: '123',
            reason: BoostRejectionReason::REPORT_UPHELD,
            statuses: [BoostStatus::APPROVED, BoostStatus::PENDING]
        )->shouldBeCalled()
            ->willReturn(true);

        $this->onAction($verdict);
    }

    public function it_should_disable_reset_password_and_email_on_hack(User $user)
    {
        $report = new Report;
        $report->setEntityUrn('urn:user:123')
            ->setEntityOwnerGuid(123)
            ->setReasonCode(17)
            ->setSubReasonCode(1);

        $verdict = new Verdict;
        $verdict->setReport($report)
            ->setUphold(true);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn($user);

        $user->set('enabled', 'no')
            ->shouldBeCalled();

        $this->saveAction->setEntity($user)
            ->willReturn($this->saveAction);

        $this->saveAction->withMutatedAttributes(['enabled'])
            ->willReturn($this->saveAction);
        
        $this->saveAction->save()
             ->shouldBeCalled();

        $this->password->randomReset($user)
            ->shouldBeCalled();

        $this->commonSessionsManager->deleteAll($user);

        $this->emailDelegate->onHack($report)
            ->shouldBeCalled();

        $this->boostManager->forceRejectByEntityGuid(
            entityGuid: '123',
            reason: BoostRejectionReason::REPORT_UPHELD,
            statuses: [BoostStatus::APPROVED, BoostStatus::PENDING]
        )->shouldBeCalled()
                ->willReturn(true);

        $this->onAction($verdict);
    }

    public function it_should_demonetize_plus_post_and_not_user_when_sub_3_strikes(
        DemonetizableEntityInterface $entity
    ) {
        $report = new Report;
        $report->setEntityUrn('urn:activity:123')
            ->setReasonCode(18);

        $verdict = new Verdict;
        $verdict->setReport($report)
            ->setUphold(true);

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->saveAction->setEntity($entity)
            ->willReturn($this->saveAction);
        
        $this->demonetizationContext->withStrategy($this->demonetizePostStrategy)
            ->shouldBeCalled()
            ->willReturn($this->demonetizationContext);

        $this->demonetizationContext->execute($entity)
            ->shouldBeCalled();

        $this->strikesManager->countStrikesInTimeWindow(Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn(0);

        $this->strikesManager->add(Argument::any())
            ->shouldBeCalled();

        $this->onAction($verdict);
    }

    public function it_should_demonetize_plus_post_and_user_when_at_3_strikes(
        DemonetizableEntityInterface $entity,
        DemonetizableEntityInterface $user
    ) {
        $report = new Report;
        $report->setEntityUrn('urn:activity:123')
            ->setReasonCode(18)
            ->setEntityOwnerGuid(234);

        $verdict = new Verdict;
        $verdict->setReport($report)
            ->setUphold(true);

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->saveAction->setEntity($entity)
            ->willReturn($this->saveAction);

        $this->demonetizationContext->withStrategy($this->demonetizePostStrategy)
            ->shouldBeCalled()
            ->willReturn($this->demonetizationContext);

        $this->demonetizationContext->execute($entity)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->strikesManager->countStrikesInTimeWindow(Argument::any(), Argument::any())
            ->shouldBeCalledTimes(2)
            ->willReturn(3);

        $this->entitiesBuilder->single(234)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->demonetizationContext->withStrategy($this->demonetizePlusUserStrategy)
            ->shouldBeCalled()
            ->willReturn($this->demonetizationContext);

        $this->demonetizationContext->execute($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onAction($verdict);
    }

    public function it_should_be_removed_and_apply_strike_if_intellectual_takedown_violation_for_non_users(Entity $entity)
    {
        $report = new Report;
        $report->setEntityUrn('urn:activity:123')
            ->setReasonCode(10);

        $verdict = new Verdict;
        $verdict->setReport($report)
            ->setUphold(true)
            ->setAction('10');

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $entity->get('type')
            ->shouldBeCalled()
            ->willReturn('activity');

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->actions->setDeletedFlag(Argument::type(Entity::class), true)
            ->shouldBeCalled();

        $this->saveAction->setEntity($entity)
            ->willReturn($this->saveAction);
        
        $this->saveAction->save()
            ->shouldBeCalled();

        $this->strikesManager->countStrikesInTimeWindow(Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn(0);

        $this->strikesManager->add(Argument::any())
            ->shouldBeCalled();

        $this->boostManager->forceRejectByEntityGuid(
            entityGuid: '123',
            reason: BoostRejectionReason::REPORT_UPHELD,
            statuses: [BoostStatus::APPROVED, BoostStatus::PENDING]
        )->shouldBeCalled()
            ->willReturn(true);

        $this->onAction($verdict);
    }


    public function it_should_apply_ban_if_intellectual_takedown_violation_for_user(User $user)
    {
        $report = new Report;
        $report->setEntityUrn('urn:user:123')
            ->setEntityOwnerGuid(123)
            ->setReasonCode(10)
            ->setSubReasonCode(2);

        $verdict = new Verdict;
        $verdict->setReport($report)
            ->setUphold(true)
            ->setAction('10');

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $user->get('type')
            ->shouldBeCalled()
            ->willReturn('user');

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->actions->setDeletedFlag(Argument::type(Entity::class), true)
            ->shouldNotBeCalled();

        $this->saveAction->setEntity($user)
            ->shouldNotBeCalled();
        
        $this->saveAction->save()
            ->shouldNotBeCalled();

        $this->strikesManager->countStrikesInTimeWindow(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->strikesManager->add(Argument::any())
            ->shouldNotBeCalled();

        $this->channelsBanManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->channelsBanManager);

        $this->channelsBanManager->ban('10.2')
            ->shouldBeCalled();

        $this->emailDelegate->onBan($report)
            ->shouldBeCalled();

        $this->boostManager->forceRejectByEntityGuid(
            entityGuid: '123',
            reason: BoostRejectionReason::REPORT_UPHELD,
            statuses: [BoostStatus::APPROVED, BoostStatus::PENDING]
        )->shouldBeCalled()
            ->willReturn(true);

        $this->onAction($verdict);
    }
}
