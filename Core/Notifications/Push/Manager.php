<?php
namespace Minds\Core\Notifications\Push;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Notifications;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\Services\PushServiceInterface;
use Minds\Core\Features;
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

    /** @var Features\Manager */
    protected $featuresManager;

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
        Features\Manager $featuresManager = null
    ) {
        $this->notificationsManager = $notificationsManager;
        $this->deviceSubscriptionsManager = $deviceSubscriptionsManager;
        $this->settingsManager = $settingsManager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->featuresManager = $featuresManager;
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
            return;
        }

        // Only if the user allows the feature flag, should we send a push notification
        if (!$this->getFeaturesManager()->setUser($toUser)->has('notifications-v3')) {
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
            return; // We can't deliver for a valid reason
        }

        // Has the user opted into this notification?
        if (!$this->getSettingsManager()->canSend($pushNotification)) {
            return; // User has opted out
        }

        $opts = new DeviceSubscriptions\DeviceSubscriptionListOpts();
        $opts->setUserGuid($notification->getToGuid());
        foreach ($this->getDeviceSubscriptionsManager()->getList($opts) as $deviceSubscription) {
            $pushNotification->setDeviceSubscription($deviceSubscription);

            $this->getService($deviceSubscription->getService())->send($pushNotification);
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
     * @return Features\Manager
     */
    protected function getFeaturesManager(): Features\Manager
    {
        if (!$this->featuresManager) {
            $this->featuresManager = Di::_()->get('Features\Manager');
        }
        return $this->featuresManager;
    }

    /**
     * @param string $service
     * @return PushServiceInterface
     */
    protected function getService(string $service): PushServiceInterface
    {
        switch ($service) {
            case DeviceSubscription::SERVICE_APNS:
                if (!$this->apnsService) {
                    $this->apnsService = new Services\ApnsService();
                }
                return $this->apnsService;
            case DeviceSubscription::SERVICE_FCM:
                if (!$this->fcmService) {
                    $this->fcmService = new Services\FcmService();
                }
                return $this->fcmService;
            case DeviceSubscription::SERVICE_WEBPUSH:
                if (!$this->webPushService) {
                    $this->webPushService = new Services\WebPushService();
                }
                return $this->webPushService;
        }
        throw new Exception('Invalid service');
    }

    /**
     * @param Services\ApnsService $apnsService
     * @return self
     */
    public function setApnsService(Services\ApnsService $apnsService): self
    {
        $this->apnsService = $apnsService;
        return $this;
    }

    /**
     * @param Services\ApnsService $apnsService
     * @return self
     */
    public function setFcmService(Services\FcmService $fcmService): self
    {
        $this->fcmService = $fcmService;
        return $this;
    }
}
