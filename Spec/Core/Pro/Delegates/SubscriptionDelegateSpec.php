<?php

namespace Spec\Minds\Core\Pro\Delegates;

use Minds\Core\Payments\Subscriptions;
use Minds\Core\Config;
use Minds\Entities\User;
use Minds\Core\Pro\Delegates\SubscriptionDelegate;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SubscriptionDelegateSpec extends ObjectBehavior
{
    private $subscriptionsManager;
    private $config;

    public function let(Subscriptions\Manager $subscriptionsManager, Config $config)
    {
        $this->beConstructedWith($subscriptionsManager, $config);
        $this->subscriptionsManager = $subscriptionsManager;
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SubscriptionDelegate::class);
    }

    public function it_should_cancel_subscriptions_on_disable(User $user)
    {
        $user->getGUID()
            ->willReturn('123');

        $this->config->get('pro')
            ->willReturn([
                'handler' => "456"
            ]);

        $this->subscriptionsManager
            ->cancelSubscriptions("123", "456")
            ->shouldBeCalled();

        $this->onDisable($user);
    }
}
