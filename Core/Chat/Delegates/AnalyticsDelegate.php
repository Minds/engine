<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Delegates;

use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Log\Logger;
use Minds\Entities\User;

/**
 * Chat analytics delegate for handling various chat events.
 */
class AnalyticsDelegate
{
    // Event names
    const CHAT_MESSAGE_SEND_EVENT_NAME = 'chat_message_send';
    const CHAT_ROOM_CREATE_EVENT_NAME = 'chat_room_create';
    const CHAT_ROOM_DELETE_EVENT_NAME = 'chat_room_delete';
    const CHAT_ROOM_LEAVE_EVENT_NAME = 'chat_room_leave';
    const CHAT_REQUEST_ACCEPT_EVENT_NAME = 'chat_request_accept';
    const CHAT_REQUEST_DECLINE_EVENT_NAME = 'chat_request_decline';

    // Property names.
    const CHAT_PROPERTY_ROOM_GUID = 'chat_room_guid';
    const CHAT_PROPERTY_ROOM_TYPE = 'chat_room_type';
    const CHAT_PROPERTY_MESSAGE_TYPE = 'chat_message_type';
    const CHAT_PROPERTY_GROUP_GUID = 'group_guid';

    public function __construct(
        private readonly PostHogService $postHogService,
        private readonly Logger $logger
    ) {
    }
   
    /**
     * Handle chat message send event.
     * @param User $actor - the event actor.
     * @param ChatMessage $message - the message.
     * @param ChatRoom $chatRoom - the chat room.
     * @return void
     */
    public function onMessageSend(
        User $actor,
        ChatMessage $message,
        ChatRoom $chatRoom
    ): void {
        try {
            $this->postHogService->capture(
                event: self::CHAT_MESSAGE_SEND_EVENT_NAME,
                user: $actor,
                properties: [
                    self::CHAT_PROPERTY_ROOM_GUID => $message->roomGuid,
                    self::CHAT_PROPERTY_ROOM_TYPE => $chatRoom->roomType?->name,
                    self::CHAT_PROPERTY_MESSAGE_TYPE => $message->messageType?->name,
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Handle chat room create event.
     * @param User $actor - the event actor.
     * @param ChatRoom $chatRoom - the chat room.
     * @return void
     */
    public function onChatRoomCreate(User $actor, ChatRoom $chatRoom): void
    {
        try {
            $this->postHogService->capture(
                event: self::CHAT_ROOM_CREATE_EVENT_NAME,
                user: $actor,
                properties: [
                    self::CHAT_PROPERTY_ROOM_GUID => $chatRoom->guid,
                    self::CHAT_PROPERTY_ROOM_TYPE => $chatRoom->roomType?->name,
                    self::CHAT_PROPERTY_GROUP_GUID => $chatRoom->groupGuid
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Handle chat room delete event.
     * @param User $actor - the event actor.
     * @param ChatRoom $chatRoom - the chat room.
     * @return void
     */
    public function onChatRoomDelete(User $actor, ChatRoom $chatRoom): void
    {
        try {
            $this->postHogService->capture(
                event: self::CHAT_ROOM_DELETE_EVENT_NAME,
                user: $actor,
                properties: [
                    self::CHAT_PROPERTY_ROOM_GUID => $chatRoom->guid,
                    self::CHAT_PROPERTY_ROOM_TYPE => $chatRoom->roomType?->name,
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Handle chat room leave event.
     * @param User $actor - the event actor.
     * @param ChatRoom $chatRoom - the chat room.
     * @return void
     */
    public function onChatRoomLeave(User $actor, ChatRoom $chatRoom): void
    {
        try {
            $this->postHogService->capture(
                event: self::CHAT_ROOM_LEAVE_EVENT_NAME,
                user: $actor,
                properties: [
                    self::CHAT_PROPERTY_ROOM_GUID => $chatRoom->guid,
                    self::CHAT_PROPERTY_ROOM_TYPE => $chatRoom->roomType?->name,
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Handle chat request accept event.
     * @param User $actor - the event actor.
     * @param ChatRoom $chatRoom - the chat room.
     * @return void
     */
    public function onChatRequestAccept(User $actor, ChatRoom $chatRoom): void
    {
        try {
            $this->postHogService->capture(
                event: self::CHAT_REQUEST_ACCEPT_EVENT_NAME,
                user: $actor,
                properties: [
                    self::CHAT_PROPERTY_ROOM_GUID => $chatRoom->guid,
                    self::CHAT_PROPERTY_ROOM_TYPE => $chatRoom->roomType?->name,
                    self::CHAT_PROPERTY_GROUP_GUID => $chatRoom->groupGuid
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Handle chat request decline event.
     * @param User $actor - the event actor.
     * @param ChatRoom $chatRoom - the chat room.
     * @return void
     */
    public function onChatRequestDecline(User $actor, ChatRoom $chatRoom): void
    {
        try {
            $this->postHogService->capture(
                event: self::CHAT_REQUEST_DECLINE_EVENT_NAME,
                user: $actor,
                properties: [
                    self::CHAT_PROPERTY_ROOM_GUID => $chatRoom->guid,
                    self::CHAT_PROPERTY_ROOM_TYPE => $chatRoom->roomType?->name,
                    self::CHAT_PROPERTY_GROUP_GUID => $chatRoom->groupGuid
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }
}
