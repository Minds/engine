<?php

namespace Spec\Minds\Core\Notifications;

use Minds\Core\Notifications\NotificationsListOpts;
use Minds\Core\Notifications\NotificationTypes;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NotificationsListOptsSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(NotificationsListOpts::class);
    }

    public function it_should_validate_group_type()
    {
        $this->setGroupType(NotificationTypes::GROUP_TYPE_COMMENTS);

        $this->shouldThrow(\Exception::class)->duringSetGroupType('invalid');
    }
}
