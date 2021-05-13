<?php

namespace Spec\Minds\Core\Notification;

use Minds\Core\Notification\NotificationEventStreamsSubscription;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NotificationEventStreamsSubscriptionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(NotificationEventStreamsSubscription::class);
    }
}
