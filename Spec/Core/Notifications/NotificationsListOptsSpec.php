<?php

namespace Spec\Minds\Core\Notifications;

use Minds\Core\Notifications\NotificationsListOpts;
use Minds\Core\Notifications\NotificationTypes;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

//ojm pin
class NotificationsListOptsSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(NotificationsListOpts::class);
    }

    public function it_should_validate_grouping_type()
    {
        $this->setGroupingType(NotificationTypes::GROUPING_TYPE_COMMENTS);

        $this->shouldThrow(\Exception::class)->duringSetGroupingType('invalid');
    }
}
