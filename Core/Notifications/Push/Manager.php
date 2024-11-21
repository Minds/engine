<?php
namespace Minds\Core\Notifications\Push;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\Services\PushServiceInterface;
use Minds\Entities\User;

class Manager
{
    /** @var Notifications\Manager */
    protected $notificationsManager;

    /** @var DeviceSubscriptions\Manager */
    protected $deviceSubscriptionsManager;

    /** @var Settings\Manager */
    protected $settingsManager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Services\ApnsService */
    protected $apnsService;

    /** @var Services\FcmService */
    protected $fcmService;

    /** @var Services\WebPushService */
    protected $webPushService;

    public function __construct(
        Notifications\Manager $notificationsManager = null,
        DeviceSubscriptions\Manager $deviceSubscriptionsManager = null,
        Settings\Manager $settingsManager = null,
        EntitiesBuilder $entitiesBuilder = null,
        private ?Logger $logger = null
    ) {
        $this->notificationsManager = $notificationsManager;
        $this->deviceSubscriptionsManager = $deviceSubscriptionsManager;
        $this->settingsManager = $settingsManager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Deliver the push notification
     * @param Notification $notification
     * @return void
     */
    public function sendPushNotification(Notification $notification): void
    {
        /** @var User */
        $toUser = $this->getEntitiesBuilder()->single($notification->getToGuid());

        if (!$toUser) {
            $this->logger->error('Push notification could not be delivered, user not found', [
                'notification' => $notification->export(),
            ]);
            return;
        }

        // TODO: Get the max read timestamp, as this will indicate how 'active' the user is

        $opts = new Notifications\NotificationsListOpts();
        $opts->setToGuid((string) $notification->getToGuid());
        $opts->setLteUuid($notification->getUuid());

        // We gather our recent 12 notifications to get on the fly merging
        $recentNotifications = iterator_to_array($this->getNotificationsManager()->getList($opts));
        foreach ($recentNotifications as $recentNotification) {
            if ($recentNotification[0]->getUrn() === $notification->getUrn()) {
                $notification = $recentNotification[0];
            }
            break;
        }

        try {
            $pushNotification = new PushNotification($notification);
            $pushNotification->setUnreadCount($this->getNotificationsManager()->getUnreadCount($toUser));
        } catch (UndeliverableException $e) {
            $this->logger->info('Push notification could not be delivered', [
                'notification' => $notification->export(),
                'exception' => $e,
            ]);
            return; // We can't deliver for a valid reason
        }

        // Has the user opted into this notification?
        if (!$this->getSettingsManager()->canSend($pushNotification)) {
            $this->logger->info('User has opted out of this notification');
            return; // User has opted out
        }

        $opts = new DeviceSubscriptions\DeviceSubscriptionListOpts();
        $opts->setUserGuid($notification->getToGuid());
        foreach ($this->getDeviceSubscriptionsManager()->getList($opts) as $deviceSubscription) {
            try {
                $this->logger->info('Sending push notification', [
                    'deviceSubscription' => $deviceSubscription->getToken(),
                ]);
                $pushNotification->setDeviceSubscription($deviceSubscription);

                $this->getService($deviceSubscription->getService())->send($pushNotification);
            } catch (\Exception $e) {
                if ($e->getCode() === 410) {
                    // Device is gone
                    $this->getDeviceSubscriptionsManager()->delete($deviceSubscription);
                    $this->logger->info('Failed as the device is gone. Cleaned up');
                } else {
                    $this->logger->error('Failed ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * @return Notifications\Manager
     */
    protected function getNotificationsManager(): Notifications\Manager
    {
        if (!$this->notificationsManager) {
            $this->notificationsManager = Di::_()->get('Notifications\Manager');
        }
        return $this->notificationsManager;
    }

    /**
     * @return DeviceSubscriptions\Manager
     */
    protected function getDeviceSubscriptionsManager(): DeviceSubscriptions\Manager
    {
        if (!$this->deviceSubscriptionsManager) {
            $this->deviceSubscriptionsManager = new DeviceSubscriptions\Manager();
        }
        return $this->deviceSubscriptionsManager;
    }

    /**
     * @return Settings\Manager
     */
    protected function getSettingsManager(): Settings\Manager
    {
        if (!$this->settingsManager) {
            $this->settingsManager = new Settings\Manager();
        }
        return $this->settingsManager;
    }

    /**
     * @return EntitiesBuilder
     */
    protected function getEntitiesBuilder(): EntitiesBuilder
    {
        if (!$this->entitiesBuilder) {
            $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
        }
        return $this->entitiesBuilder;
    }

    /**
     * @param string $service
     * @return PushServiceInterface
     */
    protected function getService(string $service): PushServiceInterface
    {
        switch ($service) {
            case DeviceSubscription::SERVICE_APNS:
                $this->apnsService = Di::_()->get(Services\ApnsService::class);
                return $this->apnsService;
            case DeviceSubscription::SERVICE_FCM:
                $this->fcmService = Di::_()->get(Services\FcmService::class);
                return $this->fcmService;
            case DeviceSubscription::SERVICE_WEBPUSH:
                $this->webPushService = Di::_()->get(Services\WebPushService::class);
                return $this->webPushService;
        }
        throw new Exception('Invalid service');
    }

}
