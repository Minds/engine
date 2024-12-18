<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Notifications;

use InvalidArgumentException;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Enums\ChatMessageTypeEnum;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomNotificationStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Notifications\Events\ChatNotificationEvent;
use Minds\Core\Chat\Notifications\Models\ImageMessageNotification;
use Minds\Core\Chat\Notifications\Models\PlainTextMessageNotification;
use Minds\Core\Chat\Notifications\Models\RichEmbedMessageNotification;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Chat\Types\ChatRoomMemberEdge;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ChatNotificationsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscriptionListOpts;
use Minds\Core\Notifications\Push\DeviceSubscriptions\Manager as DevicePushNotifSubscriptionManager;
use Minds\Core\Notifications\Push\Services\ApnsService;
use Minds\Core\Notifications\Push\Services\FcmService;
use Minds\Core\Notifications\Push\Services\PushServiceInterface;
use Minds\Core\Notifications\Push\Services\WebPushService;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use NotImplementedException;

class ChatNotificationEventsSubscription implements SubscriptionInterface
{
    private const EVENT_DATE_THRESHOLD_IN_SECONDS = 36400; // 10 minutes
    private readonly EntitiesResolver $entitiesResolver;
    private readonly RoomService $roomService;
    private readonly EntitiesBuilder $entitiesBuilder;
    private readonly DevicePushNotifSubscriptionManager $devicePushNotifSubscriptionManager;
    private readonly NotificationFactory $notificationFactory;
    private readonly FcmService $androidNotificationService;
    private readonly ApnsService $appleNotificationService;
    private readonly WebPushService $webPushNotificationService;

    public function __construct(
        ?EntitiesResolver $entitiesResolver = null,
        ?RoomService $roomService = null,
        ?EntitiesBuilder $entitiesBuilder = null,
        ?DevicePushNotifSubscriptionManager $devicePushNotifSubscriptionManager = null,
        ?NotificationFactory $notificationFactory = null,
        ?FcmService $androidNotificationService = null,
        ?ApnsService $appleNotificationService = null,
        ?WebPushService $webPushNotificationService = null,
        private ?Logger $logger = null,
    ) {
        $this->entitiesResolver = $entitiesResolver ?? Di::_()->get(EntitiesResolver::class);
        $this->roomService = $roomService ?? Di::_()->get(RoomService::class);
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->devicePushNotifSubscriptionManager = $devicePushNotifSubscriptionManager ?? Di::_()->get(DevicePushNotifSubscriptionManager::class);
        $this->notificationFactory = $notificationFactory ?? Di::_()->get(NotificationFactory::class);
        $this->androidNotificationService = $androidNotificationService ?? Di::_()->get(FcmService::class);
        $this->appleNotificationService = $appleNotificationService ?? Di::_()->get(ApnsService::class);
        $this->webPushNotificationService = $webPushNotificationService ?? Di::_()->get(WebPushService::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    public function getSubscriptionId(): string
    {
        return "chat-push-notifications";
    }

    public function getTopic(): TopicInterface
    {
        return new ChatNotificationsTopic();
    }

    public function getTopicRegex(): string
    {
        return '.*';
    }

    /**
     * @param EventInterface $event
     * @return bool
     * @throws ServerErrorException
     * @throws NotImplementedException
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ChatNotificationEvent) {
            return false;
        }

        if ($event->getTimestamp() < time() - self::EVENT_DATE_THRESHOLD_IN_SECONDS) {
            // skip old events via acknowledgement
            return true;
        }

        $chatEntity = $this->entitiesResolver->single($event->entityUrn);

        if (!$chatEntity) {
            return true; // Probably deleted
        }

        // TODO: get chat room members and send push notifications to them based on notification preference
        match (get_class($chatEntity)) {
            ChatMessage::class => $this->processChatMessage($chatEntity),
            ChatRoom::class => throw new NotImplementedException('Chat room notifications are not implemented yet'),
            default => throw new InvalidArgumentException('Invalid chat entity class'),
        };

        return true;
    }

    /**
     * @param ChatMessage $chatMessage
     * @return void
     * @throws NotImplementedException
     * @throws ServerErrorException
     */
    private function processChatMessage(ChatMessage $chatMessage): void
    {
        $this->logger->info("Processing chat from {$chatMessage->getOwnerGuid()}");

        $sender = $this->entitiesBuilder->single($chatMessage->getOwnerGuid());

        if (!$sender instanceof User) {
            return;
        }

        $roomMembers = $this->roomService->getAllRoomMembers(
            roomGuid: $chatMessage->roomGuid,
            user: $sender,
            memberStatus: [ChatRoomMemberStatusEnum::ACTIVE],
            excludeSelf: false,
        );

        $chatRoomEdge = $this->roomService->getRoom($chatMessage->roomGuid, $sender, false);
        $chatRoom = $chatRoomEdge->getNode()->chatRoom;

        $notification = match ($chatMessage->messageType) {
            ChatMessageTypeEnum::TEXT => $this->notificationFactory->createNotification(
                notificationClass: PlainTextMessageNotification::class,
                chatEntity: $chatMessage,
                chatRoom: $chatRoom,
            ),
            ChatMessageTypeEnum::RICH_EMBED => $this->notificationFactory->createNotification(
                notificationClass: RichEmbedMessageNotification::class,
                chatEntity: $chatMessage,
                chatRoom: $chatRoom,
            ),
            ChatMessageTypeEnum::IMAGE => $this->notificationFactory->createNotification(
                notificationClass: ImageMessageNotification::class,
                chatEntity: $chatMessage,
                chatRoom: $chatRoom,
            ),
            default => throw new InvalidArgumentException('Invalid chat message type'),
        };

        // Get the guids of all the room members, if not a group
        if ($chatRoom->roomType !== ChatRoomTypeEnum::GROUP_OWNED) {
            $roomMembers = iterator_to_array($roomMembers);
            $roomMemberGuids = array_map(fn (ChatRoomMemberEdge $roomMember) => $roomMember->getNode()->getGuid(), $roomMembers);
        } else {
            $roomMemberGuids = [];
        }

        foreach ($roomMembers as $roomMember) {
            if ($roomMember->getNode()->getGuid() === $sender->getGuid()) {
                continue; // Don't send to self
            }

            if ($roomMember->notificationStatus === ChatRoomNotificationStatusEnum::MUTED) {
                continue;
            }

            $receiver = $this->entitiesBuilder->single($roomMember->getNode()->getGuid());

            if (!$receiver instanceof User) {
                continue;
            }

            $this->logger->info("... sending to {$roomMember->getNode()->getGuid()}");

            // Avoid having your own name in the list
            $notification->title = $this->roomService->getRoomName($chatRoom, $receiver, $roomMemberGuids);

            $notification->setNotificationRecipient((int) $receiver->getGuid());

            $deviceSubscriptions = $this->devicePushNotifSubscriptionManager->getList(
                (new DeviceSubscriptionListOpts())
                    ->setUserGuid($roomMember->getNode()->getGuid())
            );

            foreach ($deviceSubscriptions as $deviceSubscription) {
                $this->logger->info("Delivering to device - {$deviceSubscription->getToken()}");
                $notification->setDeviceSubscription($deviceSubscription);
                try {
                    $this->getNotificationHandler($deviceSubscription->getService())->send($notification);
                } catch (\Exception $e) {
                    if ($e->getCode() === 410 || $e->getCode() === 404) {
                        // Device is gone
                        $this->devicePushNotifSubscriptionManager->delete($deviceSubscription);
                        $this->logger->info('Failed as the device is gone. Cleaned up');
                    } else {
                        $this->logger->error('Failed ' . $e->getMessage());
                    }
                }
            }

            $this->logger->info("... completed sending to {$roomMember->getNode()->getGuid()}");
        }
    }

    private function getNotificationHandler(string $notificationHandlerType): PushServiceInterface
    {
        return match ($notificationHandlerType) {
            DeviceSubscription::SERVICE_FCM => $this->androidNotificationService,
            DeviceSubscription::SERVICE_APNS => $this->appleNotificationService,
            DeviceSubscription::SERVICE_WEBPUSH => $this->webPushNotificationService,
            default => throw new InvalidArgumentException('Invalid notification handler type'),
        };
    }
}
