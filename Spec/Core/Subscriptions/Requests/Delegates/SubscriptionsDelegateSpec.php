<?php

namespace Spec\Minds\Core\Subscriptions\Requests\Delegates;

use Minds\Core\Subscriptions\Requests\Delegates\SubscriptionsDelegate;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SubscriptionsDelegateSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(SubscriptionsDelegate::class);
    }
}
