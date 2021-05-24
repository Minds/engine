<?php

namespace Spec\Minds\Core\Notifications\Push;

use Minds\Core\Notifications\Push\Manager;
use Minds\Core\Notifications;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Notifications\Push\DeviceSubscriptions;
use Minds\Core\Features;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\Services\ApnsService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Notifications\Manager */
    protected $notificationsManager;

    /** @var DeviceSubscriptions\Manager */
    protected $deviceSubscriptionsManager;
    
    /** @var EntitiesBuilder */
    protected $entitiesBuilder;
    
    /** @var Features\Manager */
    protected $featuresManager;
    
    public function let(
        Notifications\Manager $notificationsManager,
        DeviceSubscriptions\Manager $deviceSubscriptionsManager,
        EntitiesBuilder $entitiesBuilder,
        Features\Manager $featuresManager
    ) {
        $this->beConstructedWith($notificationsManager, $deviceSubscriptionsManager, $entitiesBuilder, $featuresManager);
        $this->notificationsManager = $notificationsManager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->deviceSubscriptionsManager = $deviceSubscriptionsManager;
        $this->featuresManager = $featuresManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_not_send_if_feature_flag_is_off()
    {
        $notification = new Notification();
        $notification->setToGuid(123);

        $toUser = new User();

        $this->entitiesBuilder->single('123')
            ->willReturn($toUser);

        $this->featuresManager->setUser($toUser)
            ->willReturn($this->featuresManager);
        $this->featuresManager->has('notifications-v3')
            ->willReturn(false);

        $this->sendPushNotification($notification);
    }

    public function it_should_send_a_push_notifiction(ApnsService $apnsService)
    {
        $notification = new Notification();
        $notification->setToGuid('123');

        $toUser = new User();

        $this->entitiesBuilder->single('123')
            ->willReturn($toUser);

        $this->featuresManager->setUser($toUser)
            ->willReturn($this->featuresManager);
        $this->featuresManager->has('notifications-v3')
            ->willReturn(true);

        $this->notificationsManager->getUnreadCount($toUser)
            ->willReturn(2);

        $this->deviceSubscriptionsManager->getList(Argument::that(function ($opts) {
            return $opts->getUserGuid() === '123';
        }))
            ->willReturn([
                (new DeviceSubscription())
                    ->setService(DeviceSubscription::SERVICE_APNS)
            ]);

        $this->setApnsService($apnsService);

        $apnsService->send(Argument::that(function ($pushNotification) {
            return true;
        }))
            ->shouldBeCalled();

        $this->sendPushNotification($notification);
    }
}
