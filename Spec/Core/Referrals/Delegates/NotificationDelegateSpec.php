<?php

namespace Spec\Minds\Core\Referrals\Delegates;

use Minds\Core\Referrals\Referral;
use Minds\Core\Referrals\Manager;
use Minds\Core\Referrals\Repository;
use Minds\Core\Referrals\Delegates\NotificationDelegate;
use Minds\Core\Di\Di;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Entity;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NotificationDelegateSpec extends ObjectBehavior
{
    /** @var EventsDispatcher $dispatcher */
    private $dispatcher;

    /** @var EntitiesBuilder $entitiesBuilder */
    private $entitiesBuilder;

    public function let(
        EventsDispatcher $dispatcher,
        EntitiesBuilder $entitiesBuilder
    ) {
        $this->beConstructedWith($dispatcher, $entitiesBuilder);
        $this->dispatcher=$dispatcher;
        $this->entitiesBuilder = $entitiesBuilder;
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(NotificationDelegate::class);
    }

    public function it_should_send_a_pending_referral_notification_to_referrer(Referral $referral, Entity $entity)
    {
        $referral->getReferrerGuid()
            ->shouldBeCalled()
            ->willReturn(456);

        $referral->getProspectGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->entitiesBuilder->single(123)
            ->willReturn($entity);

        $referral->getJoinTimestamp()
            ->shouldBeCalled()
            ->willReturn(null); // Referral is pending bc hasn't joined rewards yet

        $this->dispatcher->trigger('notification', 'all', Argument::that(function ($opts) {
            return $opts['notification_view'] === 'referral_pending';
        }))
            ->shouldBeCalled();

        $this->notifyReferrer($referral);
    }

    public function it_should_send_a_completed_referral_notification_to_referrer(Referral $referral, Entity $entity)
    {
        $referral->getReferrerGuid()
            ->shouldBeCalled()
            ->willReturn(456);

        $referral->getProspectGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->entitiesBuilder->single(123)
            ->willReturn($entity);

        $referral->getJoinTimestamp()
            ->shouldBeCalled()
            ->willReturn(111); // Referral is complete bc prospect has joined rewards

        $this->dispatcher->trigger('notification', 'all', Argument::that(function ($opts) {
            return $opts['notification_view'] === 'referral_complete';
        }))
            ->shouldBeCalled();

        $this->notifyReferrer($referral);
    }

    public function it_should_send_a_ping_notification_to_pending_prospect(Referral $referral, Entity $entity)
    {
        $referral->getProspectGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $referral->getReferrerGuid()
            ->shouldBeCalled()
            ->willReturn(456);

        $this->entitiesBuilder->single(456)
            ->willReturn($entity);

        $referral->getJoinTimestamp()
            ->shouldBeCalled()
            ->willReturn(); // Referral is pending bc hasn't joined rewards yet

        $this->dispatcher->trigger('notification', 'all', Argument::that(function ($opts) {
            return $opts['notification_view'] === 'referral_ping';
        }))
            ->shouldBeCalled();

        $this->notifyProspect($referral);
    }

    public function it_should_not_send_a_ping_notification_to_completed_prospect(Referral $referral, Entity $entity)
    {
        $referral->getReferrerGuid()
            ->shouldBeCalled()
            ->willReturn(456);

        $this->entitiesBuilder->single(456)
            ->willReturn($entity);

        $referral->getJoinTimestamp()
            ->shouldBeCalled()
            ->willReturn(111); // Referral is complete bc joined rewards

        $this->dispatcher->trigger('notification', 'all', Argument::that(function ($opts) {
            return $opts['notification_view'] === 'referral_ping';
        }))
            ->shouldNotBeCalled();

        $this->notifyProspect($referral);
    }
}
