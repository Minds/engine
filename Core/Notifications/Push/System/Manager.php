<?php

namespace Minds\Core\Notifications\Push\System;

use Exception;
use Minds\Api\Exportable;
use Minds\Common\Repository\Response;
use Minds\Common\Urn;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\Services\ApnsService;
use Minds\Core\Notifications\Push\Services\FcmService;
use Minds\Core\Notifications\Push\Services\PushServiceInterface;
use Minds\Core\Notifications\Push\System\Delegates\AdminPushNotificationEventStreamsDelegate;
use Minds\Core\Notifications\Push\System\Models\AdminPushNotificationRequest;
use Minds\Core\Notifications\Push\System\Models\CustomPushNotification;
use Minds\Core\Notifications\Push\System\Targets\SystemPushNotificationTargetsList;
use Minds\Core\Notifications\Push\UndeliverableException;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

/**
 *
 */
class Manager
{
    private User $user;

    public function __construct(
        private ?Repository $repository = null,
        private ?AdminPushNotificationEventStreamsDelegate $delegate = null,
        private ?ApnsService $apnsService = null,
        private ?FcmService $fcmService = null
    ) {
        $this->repository ??= new Repository();
        $this->delegate ??= new AdminPushNotificationEventStreamsDelegate();
        $this->apnsService ??= new ApnsService();
        $this->fcmService ??= new FcmService();
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
     * @throws UndeliverableException
     * @throws Exception
     */
    public function sendRequestNotifications(AdminPushNotificationRequest $notificationDetails): void
    {
        $notificationTargetHandler = SystemPushNotificationTargetsList::getTargetHandlerFromShortName($notificationDetails->getTarget());

        $deviceSubscriptions = $notificationTargetHandler->getList();

        $this->repository->updateRequestStartedOnDate($notificationDetails->getRequestId());

        /**
         * @var DeviceSubscription $deviceSubscription
         */
        foreach ($deviceSubscriptions as $deviceSubscription) {
            $notification = (new CustomPushNotification())
                ->setTitle($notificationDetails->getTitle())
                ->setBody($notificationDetails->getMessage())
                ->setUri($notificationDetails->getLink())
                ->setDeviceSubscription($deviceSubscription);

            try {
                $this->sendNotification($notification);
            } catch (UndeliverableException $e) {
                $this->repository->updateRequestCompletedOnDate(
                    $notificationDetails->getRequestId(),
                    AdminPushNotificationRequestStatus::FAILED
                );
            }
        }

        $this->repository->updateRequestCompletedOnDate(
            $notificationDetails->getRequestId(),
            AdminPushNotificationRequestStatus::DONE
        );
    }

    /**
     * @param CustomPushNotification $notification
     * @throws UndeliverableException
     * @throws Exception
     */
    public function sendNotification(CustomPushNotification $notification): void
    {
        $deviceSubscription = $notification->getDeviceSubscription();
        if (!$this->getService($deviceSubscription->getService())->send($notification)) {
            throw new UndeliverableException("Could not deliver system push notification to device " . $deviceSubscription->getToken() . " of user " . $deviceSubscription->getUserGuid());
        }
    }

    private function updateNotificationRequestStatus(string $requestId, int $status): void
    {
        $this->repository->updateRequestCompletedOnDate(
            $requestId,
            AdminPushNotificationRequestStatus::tryFromValue($status)
        );
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
        $requestId = explode(':', $urn->getNss())[0];

        return $this->repository->getByRequestId($requestId);
    }
}
