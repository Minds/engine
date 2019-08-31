<?php

namespace Spec\Minds\Core\Wire\Subscriptions;

use Minds\Core\Di\Di;
use Minds\Core\Wire\Manager as WireManager;
use Minds\Core\Payments\Subscriptions\Manager as SubscriptionsManager;
use Minds\Core\Payments\Subscriptions\Repository;
use Minds\Core\Wire\Exceptions\WalletNotSetupException;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Wire\Subscriptions\Manager');
    }

    public function it_should_create_a_subscription(
        WireManager $wireManager,
        SubscriptionsManager $subscriptionsManager
    ) {
        $this->beConstructedWith($wireManager, $subscriptionsManager);


        $sender = new User();
        $sender->guid = 123;
        $receiver = new User();
        $receiver->guid = 456;

        $subscriptionsManager->setSubscription(Argument::that(function ($subscription) {
            return $subscription->getUser()->guid == 123
                && $subscription->getEntity()->guid == 456
                && $subscription->getAmount() == 5;
        }))
            ->willReturn(123);
        $subscriptionsManager->create()->shouldBeCalled();
        
        $this->setAmount(5)
            ->setSender($sender)
            ->setReceiver($receiver);
        $this->create()->shouldBeString();
    }
}
