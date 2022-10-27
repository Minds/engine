<?php

namespace Spec\Minds\Core\Supermind\Notifications\Push;

use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\DeviceSubscriptions\Manager as DeviceSubscriptionsManager;
use Minds\Core\Notifications\Push\System\Builders\Supermind\SupermindAcceptedNotificationBuilder;
use Minds\Core\Notifications\Push\System\Builders\Supermind\SupermindDeclinedNotificationBuilder;
use Minds\Core\Notifications\Push\System\Builders\Supermind\SupermindExpiredNotificationBuilder;
use Minds\Core\Notifications\Push\System\Builders\Supermind\SupermindExpiring24HoursNotificationBuilder;
use Minds\Core\Notifications\Push\System\Builders\Supermind\SupermindReceivedNotificationBuilder;
use Minds\Core\Notifications\Push\System\Manager as PushManager;
use Minds\Core\Notifications\Push\System\Models\CustomPushNotification;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\Notifications\Push\Manager;
use Minds\Core\Supermind\Notifications\SupermindNotificationType;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var PushManager */
    private $pushManager;

    /** @var DeviceSubscriptionsManager */
    private $deviceSubscriptionManager;

    /** @var SupermindReceivedNotificationBuilder */
    private $receivedNotificationBuilder;

    /** @var SupermindDeclinedNotificationBuilder */
    private $declinedNotificationBuilder;

    /** @var SupermindAcceptedNotificationBuilder */
    private $acceptedNotificationBuilder;

    /** @var SupermindExpiring24HoursNotificationBuilder */
    private $expiring24HoursNotificationBuilder;

    /** @var SupermindExpiredNotificationBuilder */
    private $expiredNotificationBuilder;

    public function let(
        PushManager $pushManager,
        DeviceSubscriptionsManager $deviceSubscriptionManager,
        SupermindReceivedNotificationBuilder $receivedNotificationBuilder,
        SupermindDeclinedNotificationBuilder $declinedNotificationBuilder,
        SupermindAcceptedNotificationBuilder $acceptedNotificationBuilder,
        SupermindExpiring24HoursNotificationBuilder $expiring24HoursNotificationBuilder,
        SupermindExpiredNotificationBuilder $expiredNotificationBuilder
    ) {
        $this->beConstructedWith(
            $pushManager,
            $deviceSubscriptionManager,
            $receivedNotificationBuilder,
            $declinedNotificationBuilder,
            $acceptedNotificationBuilder,
            $expiring24HoursNotificationBuilder,
            $expiredNotificationBuilder
        );

        $this->pushManager = $pushManager;
        $this->deviceSubscriptionManager = $deviceSubscriptionManager;
        $this->receivedNotificationBuilder = $receivedNotificationBuilder;
        $this->declinedNotificationBuilder = $declinedNotificationBuilder;
        $this->acceptedNotificationBuilder = $acceptedNotificationBuilder;
        $this->expiring24HoursNotificationBuilder = $expiring24HoursNotificationBuilder;
        $this->expiredNotificationBuilder = $expiredNotificationBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_send_for_a_supermind_request_received(
        SupermindRequest $supermindRequest,
        DeviceSubscription $deviceSubscription1,
        DeviceSubscription $deviceSubscription2,
        CustomPushNotification $customPushNotification
    ) {
        $notificationType = SupermindNotificationType::OFFER_RECEIVED;
        $senderGuid = 123;
        $receiverGuid = 234;

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $this->deviceSubscriptionManager->getList(Argument::any())
            ->shouldBeCalled()
            ->willReturn([
                $deviceSubscription1,
                $deviceSubscription2
            ]);

        $this->receivedNotificationBuilder->withSupermindRequest($supermindRequest)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->receivedNotificationBuilder);
            
        $customPushNotification->setDeviceSubscription($deviceSubscription1)
            ->shouldBeCalledTimes(1)
            ->willReturn($customPushNotification);
        
        $customPushNotification->setDeviceSubscription($deviceSubscription2)
            ->shouldBeCalledTimes(1)
            ->willReturn($customPushNotification);

        $this->receivedNotificationBuilder->build()
            ->shouldBeCalledTimes(2)
            ->willReturn($customPushNotification);

        $this->pushManager->sendNotification($customPushNotification, 'supermind')
            ->shouldBeCalledTimes(2);

        $this->setSupermindRequest($supermindRequest, $notificationType);
        $this->send();
    }


    public function it_should_send_for_a_supermind_request_declined(
        SupermindRequest $supermindRequest,
        DeviceSubscription $deviceSubscription1,
        DeviceSubscription $deviceSubscription2,
        CustomPushNotification $customPushNotification
    ) {
        $notificationType = SupermindNotificationType::OFFER_DECLINED;
        $senderGuid = 123;
        $receiverGuid = 234;

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $this->deviceSubscriptionManager->getList(Argument::any())
            ->shouldBeCalled()
            ->willReturn([
                $deviceSubscription1,
                $deviceSubscription2
            ]);

        $this->declinedNotificationBuilder->withSupermindRequest($supermindRequest)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->declinedNotificationBuilder);
            
        $customPushNotification->setDeviceSubscription($deviceSubscription1)
            ->shouldBeCalledTimes(1)
            ->willReturn($customPushNotification);
        
        $customPushNotification->setDeviceSubscription($deviceSubscription2)
            ->shouldBeCalledTimes(1)
            ->willReturn($customPushNotification);

        $this->declinedNotificationBuilder->build()
            ->shouldBeCalledTimes(2)
            ->willReturn($customPushNotification);

        $this->pushManager->sendNotification($customPushNotification, 'supermind')
            ->shouldBeCalledTimes(2);

        $this->setSupermindRequest($supermindRequest, $notificationType);
        $this->send();
    }

    public function it_should_send_for_a_supermind_request_accepted(
        SupermindRequest $supermindRequest,
        DeviceSubscription $deviceSubscription1,
        DeviceSubscription $deviceSubscription2,
        CustomPushNotification $customPushNotification
    ) {
        $notificationType = SupermindNotificationType::OFFER_ACCEPTED;
        $senderGuid = 123;
        $receiverGuid = 234;

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $this->deviceSubscriptionManager->getList(Argument::any())
            ->shouldBeCalled()
            ->willReturn([
                $deviceSubscription1,
                $deviceSubscription2
            ]);

        $this->acceptedNotificationBuilder->withSupermindRequest($supermindRequest)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->acceptedNotificationBuilder);
            
        $customPushNotification->setDeviceSubscription($deviceSubscription1)
            ->shouldBeCalledTimes(1)
            ->willReturn($customPushNotification);
        
        $customPushNotification->setDeviceSubscription($deviceSubscription2)
            ->shouldBeCalledTimes(1)
            ->willReturn($customPushNotification);

        $this->acceptedNotificationBuilder->build()
            ->shouldBeCalledTimes(2)
            ->willReturn($customPushNotification);

        $this->pushManager->sendNotification($customPushNotification, 'supermind')
            ->shouldBeCalledTimes(2);

        $this->setSupermindRequest($supermindRequest, $notificationType);
        $this->send();
    }

    public function it_should_send_for_a_supermind_request_expiring_in_24_hours(
        SupermindRequest $supermindRequest,
        DeviceSubscription $deviceSubscription1,
        DeviceSubscription $deviceSubscription2,
        CustomPushNotification $customPushNotification
    ) {
        $notificationType = SupermindNotificationType::OFFER_EXPIRING_24;
        $senderGuid = 123;
        $receiverGuid = 234;

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $this->deviceSubscriptionManager->getList(Argument::any())
            ->shouldBeCalled()
            ->willReturn([
                $deviceSubscription1,
                $deviceSubscription2
            ]);

        $this->expiring24HoursNotificationBuilder->withSupermindRequest($supermindRequest)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->expiring24HoursNotificationBuilder);
            
        $customPushNotification->setDeviceSubscription($deviceSubscription1)
            ->shouldBeCalledTimes(1)
            ->willReturn($customPushNotification);
        
        $customPushNotification->setDeviceSubscription($deviceSubscription2)
            ->shouldBeCalledTimes(1)
            ->willReturn($customPushNotification);

        $this->expiring24HoursNotificationBuilder->build()
            ->shouldBeCalledTimes(2)
            ->willReturn($customPushNotification);

        $this->pushManager->sendNotification($customPushNotification, 'supermind')
            ->shouldBeCalledTimes(2);

        $this->setSupermindRequest($supermindRequest, $notificationType);
        $this->send();
    }

    public function it_should_send_for_a_supermind_request_expired(
        SupermindRequest $supermindRequest,
        DeviceSubscription $deviceSubscription1,
        DeviceSubscription $deviceSubscription2,
        CustomPushNotification $customPushNotification
    ) {
        $notificationType = SupermindNotificationType::OFFER_EXPIRED;
        $senderGuid = 123;
        $receiverGuid = 234;

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $this->deviceSubscriptionManager->getList(Argument::any())
            ->shouldBeCalled()
            ->willReturn([
                $deviceSubscription1,
                $deviceSubscription2
            ]);

        $this->expiredNotificationBuilder->withSupermindRequest($supermindRequest)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->expiredNotificationBuilder);
            
        $customPushNotification->setDeviceSubscription($deviceSubscription1)
            ->shouldBeCalledTimes(1)
            ->willReturn($customPushNotification);
        
        $customPushNotification->setDeviceSubscription($deviceSubscription2)
            ->shouldBeCalledTimes(1)
            ->willReturn($customPushNotification);

        $this->expiredNotificationBuilder->build()
            ->shouldBeCalledTimes(2)
            ->willReturn($customPushNotification);

        $this->pushManager->sendNotification($customPushNotification, 'supermind')
            ->shouldBeCalledTimes(2);

        $this->setSupermindRequest($supermindRequest, $notificationType);
        $this->send();
    }

    public function it_should_throw_an_exception_if_supermind_request_is_not_set(SupermindRequest $supermindRequest)
    {
        $this->setSupermindRequest(null, null);
        $this->shouldThrow(ServerErrorException::class)->during('send', []);
    }
    
    public function it_should_throw_an_exception_for_unsupported_notification_type(SupermindRequest $supermindRequest)
    {
        $notificationType = 99;
        $senderGuid = 123;
        $receiverGuid = 234;

        $supermindRequest->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid);

        $supermindRequest->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $this->setSupermindRequest($supermindRequest, $notificationType);
        $this->shouldThrow(ServerErrorException::class)->during('send', []);
    }
}
