<?php

namespace Spec\Minds\Core\Pro\Domain;

use Exception;
use Minds\Core\Pro\Domain\Subscription;
use Minds\Core\Subscriptions\Manager as SubscriptionsManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SubscriptionSpec extends ObjectBehavior
{
    /** @var SubscriptionsManager */
    protected $subscriptionsManager;

    public function let(
        SubscriptionsManager $subscriptionsManager
    ) {
        $this->subscriptionsManager = $subscriptionsManager;

        $this->beConstructedWith($subscriptionsManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Subscription::class);
    }

    public function it_should_subscribe(
        User $user,
        User $subscriber
    ) {
        $this->subscriptionsManager->setSubscriber($subscriber)
            ->shouldBeCalled()
            ->willReturn($this->subscriptionsManager);

        $this->subscriptionsManager->isSubscribed($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->subscriptionsManager->subscribe($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setUser($user)
            ->setSubscriber($subscriber)
            ->shouldNotThrow(Exception::class)
            ->duringSubscribe(false);
    }
}
