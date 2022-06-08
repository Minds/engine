<?php

namespace Minds\Core\Notifications\Push\System;

use Exception;
use Minds\Api\Exportable;
use Minds\Common\Repository\Response;
use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\Services\ApnsService;
use Minds\Core\Notifications\Push\Services\FcmService;
use Minds\Core\Notifications\Push\Services\PushServiceInterface;
use Minds\Core\Notifications\Push\Services\WebPushService;
use Minds\Core\Notifications\Push\Settings\Manager as SettingsManager;
use Minds\Core\Notifications\Push\Settings\PushSetting;
use Minds\Core\Notifications\Push\Settings\SettingsListOpts;
use Minds\Core\Notifications\Push\System\Delegates\AdminPushNotificationEventStreamsDelegate;
use Minds\Core\Notifications\Push\System\Models\AdminPushNotificationRequest;
use Minds\Core\Notifications\Push\System\Models\AdminPushNotificationRequestCounters;
use Minds\Core\Notifications\Push\System\Models\CustomPushNotification;
use Minds\Core\Notifications\Push\System\Targets\SystemPushNotificationTargetsList;
use Minds\Core\Notifications\Push\UndeliverableException;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\SkippingException;

/**
 *
 */
class Manager
{
    private User $user;
    private Logger $logger;

    public function __construct(
        private ?Repository $repository = null,
        private ?AdminPushNotificationEventStreamsDelegate $delegate = null,
        private ?ApnsService $apnsService = null,
        private ?FcmService $fcmService = null,
        private ?WebPushService $webPushService = null,
        private ?SettingsManager $pushSettingsManager = null
    ) {
        $this->repository ??= new Repository();
        $this->delegate ??= new AdminPushNotificationEventStreamsDelegate();
        $this->apnsService ??= new ApnsService();
        $this->fcmService ??= new FcmService();
        $this->webPushService ??= new WebPushService();
        $this->pushSettingsManager ??= new SettingsManager();
        $this->logger = Di::_()->get("Logger");
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        $this->repository->setUser($user);
        return $this;
    }

    /**
     * @param array $requestNotificationDetails
     * @return Response
     * @throws Exception
     */
    public function add(array $requestNotificationDetails): Response
    {
        $notificationDetails = $this->prepareNotificationDetails($requestNotificationDetails);

        $this->repository->add($notificationDetails);

        $this->delegate->onAdd($notificationDetails);

        return new Response([
            'notification' => $notificationDetails
        ]);
    }

    private function prepareNotificationDetails(array $requestNotificationDetails): AdminPushNotificationRequest
    {
        return (new AdminPushNotificationRequest())
            ->setAuthorGuid($this->user->getGuid())
            ->setTitle($requestNotificationDetails['notificationTitle'])
            ->setMessage($requestNotificationDetails['notificationMessage'])
            ->setLink($requestNotificationDetails['notificationLink'])
            ->setCreatedAt(time())
            ->setCounter(0)
            ->setSuccessfulCounter(0)
            ->setFailedCounter(0)
            ->setSkippedCounter(0)
            ->setTarget($requestNotificationDetails['notificationTarget']);
    }

    /**
     * @return Response
     * @throws ServerErrorException
     */
    public function getCompletedRequests(): Response
    {
        return new Response([
            'notifications' => Exportable::_($this->repository->getCompletedRequests())
        ]);
    }

    /**
     * @param AdminPushNotificationRequest $notificationDetails
     * @return bool
     * @throws Exception
     */
    public function sendRequestNotifications(AdminPushNotificationRequest $notificationDetails): bool
    {
        $logPrefix = "[SystemPush]: {$notificationDetails->getType()}:{$notificationDetails->getRequestUuid()}";
        $this->logger->info("$logPrefix: starting send");

        $notificationTargetHandler = SystemPushNotificationTargetsList::getTargetHandlerFromShortName($notificationDetails->getTarget());

        $deviceSubscriptions = $notificationTargetHandler->getList();

        $this->logger->info("$logPrefix: notifications target handler created");

        $this->repository->updateRequestStartedOnDate($notificationDetails->getType(), $notificationDetails->getRequestUuid());

        $requestCounters = new AdminPushNotificationRequestCounters();
        /**
         * @var DeviceSubscription $deviceSubscription
         */
        foreach ($deviceSubscriptions as $deviceSubscription) {
            $requestCounters->increaseTotalNotifications();

            $notification = (new CustomPushNotification())
                ->setTitle($notificationDetails->getTitle())
                ->setBody($notificationDetails->getMessage())
                ->setUri($notificationDetails->getLink())
                ->setDeviceSubscription($deviceSubscription);

            try {
                $this->sendNotification($notification, NotificationTypes::GROUPING_TYPE_COMMUNITY_UPDATES);
                $this->logger->info("$logPrefix: sending ({$requestCounters->getTotalNotifications()}) - {$deviceSubscription->getUserGuid()}");
                $requestCounters->increaseSuccessfulNotifications();
            } catch (UndeliverableException $e) {
                $this->logger->error("$logPrefix: sending ({$requestCounters->getTotalNotifications()}) - {$deviceSubscription->getUserGuid()} - failed {$e->getMessage()}");
                $requestCounters->increaseFailedNotifications();
                continue;
            } catch (SkippingException $e) {
                $this->logger->error("$logPrefix: sending ({$requestCounters->getTotalNotifications()}) - {$deviceSubscription->getUserGuid()} - skipped {$e->getMessage()}");
                $requestCounters->increaseSkippedNotifications();
            }

            if ($requestCounters->getTotalNotifications() % 200 == 0) {
                $this->repository->updateRequestCounters(
                    $notificationDetails->getType(),
                    $notificationDetails->getRequestUuid(),
                    $requestCounters
                );
            }
        }

        $this->repository->updateRequestCompletedOnDate(
            $notificationDetails->getType(),
            $notificationDetails->getRequestUuid(),
            AdminPushNotificationRequestStatus::DONE
        );

        $this->repository->updateRequestCounters(
            $notificationDetails->getType(),
            $notificationDetails->getRequestUuid(),
            $requestCounters
        );

        $this->logger->info("$logPrefix: completed");

        return true;
    }

    /**
     * @param CustomPushNotification $notification
     * @param string $notificationGroupToCheck
     * @throws SkippingException
     * @throws UndeliverableException
     */
    public function sendNotification(CustomPushNotification $notification, string $notificationGroupToCheck): void
    {
        $deviceSubscription = $notification->getDeviceSubscription();

        if (!$this->isNotificationTypeSettingActive($deviceSubscription->getUserGuid(), $notificationGroupToCheck)) {
            throw new SkippingException("Notification setting is not active. Skipping delivery of system push notification to device " . $deviceSubscription->getToken() . " of user " . $deviceSubscription->getUserGuid());
        }

        if (!$this->getService($deviceSubscription->getService())->send($notification)) {
            throw new UndeliverableException("Could not deliver system push notification to device " . $deviceSubscription->getToken() . " of user " . $deviceSubscription->getUserGuid());
        }
    }

    private function isNotificationTypeSettingActive(string $userGuid, string $notificationGroupToCheck): bool
    {
        $notificationSettings = $this->pushSettingsManager->getList(
            (new SettingsListOpts())
                ->setUserGuid($userGuid)
        );

        $notificationGroupToCheckStatus = array_filter($notificationSettings, function (PushSetting $setting, int $key) use ($notificationGroupToCheck): bool {
            return $setting->getNotificationGroup() == $notificationGroupToCheck && $setting->getEnabled();
        }, ARRAY_FILTER_USE_BOTH);

        return count($notificationGroupToCheckStatus);
    }

    /**
     * @param string $service
     * @return PushServiceInterface
     * @throws Exception
     */
    private function getService(string $service): PushServiceInterface
    {
        return match ($service) {
            DeviceSubscription::SERVICE_APNS => $this->apnsService,
            DeviceSubscription::SERVICE_FCM => $this->fcmService,
            DeviceSubscription::SERVICE_WEBPUSH => $this->webPushService,
            default => throw new Exception('Invalid service'),
        };
    }

    /**
     * @param string|Urn $urn
     * @return AdminPushNotificationRequest
     * @throws Exception
     */
    public function getRequestByUrn(string|Urn $urn): AdminPushNotificationRequest
    {
        if (is_string($urn)) {
            $urn = new Urn($urn);
        }
        $identifier = explode(':', $urn->getNss());

        return $this->repository->getByRequestId($identifier[0], $identifier[1]);
    }
}
