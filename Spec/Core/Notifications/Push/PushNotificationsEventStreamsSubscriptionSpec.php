<?php

namespace Spec\Minds\Core\Notifications\Push;

use Minds\Core\EventStreams\NotificationEvent;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\Push\PushNotificationsEventStreamsSubscription;
use Minds\Core\Notifications\Push\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PushNotificationsEventStreamsSubscriptionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(PushNotificationsEventStreamsSubscription::class);
    }

    public function it_should_send_a_push_notification_from_notifiction_event(Manager $manager)
    {
        $this->beConstructedWith($manager);

        $notification = new Notification();
        $event = new NotificationEvent();
        $event->setNotification($notification);

        $manager->sendPushNotification($notification)
          ->shouldBeCalled();

       
        $this->consume($event)->shouldBe(true);
    }

    public function it_should_not_send_if_already_viewed(Manager $manager)
    {
        $this->beConstructedWith($manager);

        $notification = new Notification();
        $notification->setReadTimestamp(time());
        $event = new NotificationEvent();
        $event->setNotification($notification);

        $manager->sendPushNotification($notification)
          ->shouldNotBeCalled();

       
        $this->consume($event)->shouldBe(true);
    }
}
