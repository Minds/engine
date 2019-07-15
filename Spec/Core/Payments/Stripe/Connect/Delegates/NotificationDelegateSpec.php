<?php

namespace Spec\Minds\Core\Payments\Stripe\Connect\Delegates;

use Minds\Core\Payments\Stripe\Connect\Delegates\NotificationDelegate;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NotificationDelegateSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(NotificationDelegate::class);
    }
}
