<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Notifications;

use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\Notifications\Push\DeviceSubscriptions\Manager as DevicePushNotifSubscriptionManager;
use Minds\Core\Notifications\Push\Services\ApnsService;
use Minds\Core\Notifications\Push\Services\FcmService;

class NotificationsProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            ChatNotificationEventsSubscription::class,
            fn (Di $di): ChatNotificationEventsSubscription => new ChatNotificationEventsSubscription(
                entitiesResolver: $di->get(EntitiesResolver::class),
                roomService: $di->get(RoomService::class),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                devicePushNotifSubscriptionManager: $di->get(DevicePushNotifSubscriptionManager::class),
                notificationFactory: $di->get(NotificationFactory::class),
                androidNotificationService: $di->get(FcmService::class),
                appleNotificationService: $di->get(ApnsService::class),
            )
        );

        $this->di->bind(
            NotificationFactory::class,
            fn (Di $di): NotificationFactory => new NotificationFactory(
                entitiesBuilder: $di->get('EntitiesBuilder')
            )
        );
    }
}
