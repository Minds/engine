<?php

namespace Spec\Minds\Core\Comments\Delegates;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ThreadNotificationsSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Comments\Delegates\ThreadNotifications');
    }

    // EventsDispatcher cannot be tested yet
}
