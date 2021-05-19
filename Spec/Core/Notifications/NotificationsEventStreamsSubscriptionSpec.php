<?php

namespace Spec\Minds\Core\Notifications;

use Minds\Core\Notifications\NotificationsEventStreamsSubscription;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NotificationsEventStreamsSubscriptionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(NotificationsEventStreamsSubscription::class);
    }
}
