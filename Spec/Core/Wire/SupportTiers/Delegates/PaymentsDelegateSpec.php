<?php

namespace Spec\Minds\Core\Wire\SupportTiers\Delegates;

use Minds\Core\Wire\SupportTiers\Delegates\PaymentsDelegate;
use Minds\Core\Wire\SupportTiers\SupportTier;
use Minds\Core\Sessions\ActiveSession;
use Minds\Core\Payments\Subscriptions;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PaymentsDelegateSpec extends ObjectBehavior
{
    /** @var Subscriptions\Manager */
    protected $subscriptionsManager;

    /** @var ActiveSession */
    protected $activeSession;

    public function let(Subscriptions\Manager $subscriptionsManager, ActiveSession $activeSession)
    {
        $this->beConstructedWith($subscriptionsManager, null, $activeSession);
        $this->subscriptionsManager = $subscriptionsManager;
        $this->activeSession = $activeSession;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PaymentsDelegate::class);
    }

    public function it_should_pair_a_subscription()
    {
        $supportTier = new SupportTier();
        $supportTier->setEntityGuid("123")
            ->setUsd(5);
           

        $user = new User();
        $user->guid = "456";
        $this->activeSession->getUser()
            ->willReturn($user);

        $this->subscriptionsManager->getList(Argument::any())
            ->willReturn([
                (new Subscriptions\Subscription())
                    ->setPaymentMethod('usd')
                    ->setAmount(500)
                    ->setId('urn-here'),
            ]);

        $this->hydrate($supportTier)
            ->getSubscriptionUrn()
            ->shouldBe('urn-here');
    }
}
