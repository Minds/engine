<?php

namespace Spec\Minds\Core\Notifications\Push;

use ArrayIterator;
use Minds\Core\Di\Di;
use Minds\Core\Notifications\Push\Manager;
use Minds\Core\Notifications;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Notifications\Push\DeviceSubscriptions;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\Services\ApnsService;
use Minds\Core\Notifications\Push\Settings;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Notifications\Manager */
    protected $notificationsManager;

    /** @var DeviceSubscriptions\Manager */
    protected $deviceSubscriptionsManager;

    /** @var Settings\Manager */
    protected $settingsManager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function let(
        Notifications\Manager $notificationsManager,
        DeviceSubscriptions\Manager $deviceSubscriptionsManager,
        Settings\Manager $settingsManager,
        EntitiesBuilder $entitiesBuilder
    ) {
        $this->beConstructedWith($notificationsManager, $deviceSubscriptionsManager, $settingsManager, $entitiesBuilder);
        $this->notificationsManager = $notificationsManager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->settingsManager = $settingsManager;
        $this->deviceSubscriptionsManager = $deviceSubscriptionsManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_send_a_push_notifiction(ApnsService $apnsService)
    {
        $notification = new Notification();
        $notification->setToGuid('123');
        $notification->setUuid('uuid-1');
        $notification->setType(NotificationTypes::TYPE_COMMENT);

        $toUser = new User();

        $this->entitiesBuilder->single('123')
            ->willReturn($toUser);

        $this->notificationsManager->getList(Argument::that(function ($opts) {
            return $opts->getLteUuid() === 'uuid-1';
        }))
            ->willReturn(new ArrayIterator([
                [$notification,'']
            ]));

        $this->notificationsManager->getUnreadCount($toUser)
            ->willReturn(2);

        $this->deviceSubscriptionsManager->getList(Argument::that(function ($opts) {
            return $opts->getUserGuid() === '123';
        }))
            ->willReturn([
                (new DeviceSubscription())
                    ->setService(DeviceSubscription::SERVICE_APNS)
            ]);

        $this->settingsManager->canSend(Argument::any())
            ->willReturn(true);

        Di::_()->bind(ApnsService::class, function ($di) use ($apnsService) {
            return $apnsService->getWrappedObject();
        });

        $apnsService->send(Argument::that(function ($pushNotification) {
            return true;
        }))
            ->shouldBeCalled();

        $this->sendPushNotification($notification);
    }
}
