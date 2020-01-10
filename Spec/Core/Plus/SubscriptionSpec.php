<?php

namespace Spec\Minds\Core\Plus;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\Payments\Stripe\Stripe;
use Minds\Core\Payments\Subscriptions\Manager;
use Minds\Core\Payments\Subscriptions\Repository;
use Minds\Core\Payments\Subscriptions\Subscription;
use Minds\Entities\User;

class SubscriptionSpec extends ObjectBehavior
{
    public function it_is_initializable(Stripe $stripe)
    {
        $this->beConstructedWith($stripe);
        $this->shouldHaveType('Minds\Core\Plus\Subscription');
    }

    public function it_should_return_if_a_subscription_is_active(
        Stripe $stripe,
        Repository $repo,
        User $user
    ) {
        $this->beConstructedWith($stripe, null, $repo);

        $user->isPlus()
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setUser($user)
            ->isActive()
            ->shouldBe(true);
    }

    public function it_should_return_false_if_a_subscription_is_active(
        Stripe $stripe,
        Repository $repo,
        User $user
    ) {
        $this->beConstructedWith($stripe, null, $repo);

        $user->isPlus()
            ->shouldBeCalled()
            ->willReturn(false);

        $this
            ->setUser($user)
            ->isActive()
            ->shouldBe(false);
    }

    public function is_should_create_a_new_subscription(
        Stripe $stripe,
        Manager $manager,
        Subscription $subscription
    ) {
        $this->beConstructedWith($stripe, $manager);
        
        $subscription->setInterval('monthly')
            ->shouldBeCalled()
            ->willReturn($subscription);
        
        $subscription->setAmount(5)
            ->shouldBeCalled()
            ->willReturn($subscription);

        $manager->setSubscription($subscription)->shouldBeCalled();
        $manager->create()->shouldBeCalled();

        $this->create($subscription)
            ->shouldReturn($this);
    }

    public function is_should_cancel_exisiting_subscription(
        Stripe $stripe,
        Repository $repository,
        Manager $manager,
        Subscription $subscription
    ) {
        $this->beConstructedWith($stripe, $manager, $repository);
        
        $repository->getList([
                'plan_id' => 'plus',
                'payment_method' => 'money',
                'user_guid' => 123
            ])
            ->shouldBeCalled()
            ->willReturn([ $subscription ]);
        
        $repository->delete($subscripton)
            ->shouldBeCalled()
            ->willReturn(true);
        
        $user = new User();
        $user->guid = 123;

        $this->setUser($user);

        $this->cancel()
            ->shouldReturn($this);
    }
}
