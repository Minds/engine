<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Chat\Delegates;

use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Chat\Delegates\AnalyticsDelegate;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Enums\ChatMessageTypeEnum;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use ReflectionClass;

class AnalyticsDelegateSpec extends ObjectBehavior
{
    private Collaborator $postHogServiceMock;
    private Collaborator $loggerMock;

    private ReflectionClass $chatRoomMockFactory;
    private ReflectionClass $chatMessageMockFactory;

    public function let(
        PostHogService $postHogService,
        Logger $logger
    ): void {
        $this->postHogServiceMock = $postHogService;
        $this->loggerMock = $logger;

        $this->chatRoomMockFactory = new ReflectionClass(ChatRoom::class);
        $this->chatMessageMockFactory = new ReflectionClass(ChatMessage::class);

        $this->beConstructedWith(
            $this->postHogServiceMock,
            $this->loggerMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(AnalyticsDelegate::class);
    }

    public function it_should_handle_on_message_send_events(
        User $user,
    ): void {
        $roomGuid = 123;
        $roomType = ChatRoomTypeEnum::ONE_TO_ONE;
        $messageType = ChatMessageTypeEnum::TEXT;

        $chatRoom = $this->generateChatRoomMock($roomGuid, $roomType);
        $chatMessage = $this->generateChatMessageMock($roomGuid, $messageType);

        $this->postHogServiceMock->capture(
            event: 'chat_message_send',
            user: $user,
            properties: [
                'chat_room_guid' => $roomGuid,
                'chat_room_type' => $roomType->name,
                'chat_message_type' => $messageType->name,
            ]
        )->shouldBeCalled();

        $this->onMessageSend($user, $chatMessage, $chatRoom);
    }

    public function it_should_handle_on_room_delete_events(
        User $user,
    ): void {
        $roomGuid = 123;
        $roomType = ChatRoomTypeEnum::ONE_TO_ONE;

        $chatRoom = $this->generateChatRoomMock($roomGuid, $roomType);

        $this->postHogServiceMock->capture(
            event: 'chat_room_delete',
            user: $user,
            properties: [
                'chat_room_guid' => $roomGuid,
                'chat_room_type' => $roomType->name,
            ]
        )->shouldBeCalled();

        $this->onChatRoomDelete($user, $chatRoom);
    }

    public function it_should_handle_on_room_leave_events(
        User $user,
    ): void {
        $roomGuid = 123;
        $roomType = ChatRoomTypeEnum::ONE_TO_ONE;

        $chatRoom = $this->generateChatRoomMock($roomGuid, $roomType);

        $this->postHogServiceMock->capture(
            event: 'chat_room_leave',
            user: $user,
            properties: [
                'chat_room_guid' => $roomGuid,
                'chat_room_type' => $roomType->name,
            ]
        )->shouldBeCalled();

        $this->onChatRoomLeave($user, $chatRoom);
    }

    public function it_should_handle_on_request_accept_events(
        User $user,
    ): void {
        $roomGuid = 123;
        $groupGuid = 234;
        $roomType = ChatRoomTypeEnum::ONE_TO_ONE;

        $chatRoom = $this->generateChatRoomMock($roomGuid, $roomType, $groupGuid);

        $this->postHogServiceMock->capture(
            event: 'chat_request_accept',
            user: $user,
            properties: [
                'chat_room_guid' => $roomGuid,
                'chat_room_type' => $roomType->name,
                'group_guid' => $groupGuid
            ]
        )->shouldBeCalled();

        $this->onChatRequestAccept($user, $chatRoom);
    }

    public function it_should_handle_on_request_decline_events(
        User $user,
    ): void {
        $roomGuid = 123;
        $groupGuid = 234;
        $roomType = ChatRoomTypeEnum::ONE_TO_ONE;

        $chatRoom = $this->generateChatRoomMock($roomGuid, $roomType, $groupGuid);

        $this->postHogServiceMock->capture(
            event: 'chat_request_decline',
            user: $user,
            properties: [
                'chat_room_guid' => $roomGuid,
                'chat_room_type' => $roomType->name,
                'group_guid' => $groupGuid
            ]
        )->shouldBeCalled();

        $this->onChatRequestDecline($user, $chatRoom);
    }

    private function generateChatRoomMock(
        int $roomGuid,
        ChatRoomTypeEnum $roomType,
        int $groupGuid = null
    ): ChatRoom {
        $chatRoom = $this->chatRoomMockFactory->newInstanceWithoutConstructor();
        
        $this->chatRoomMockFactory->getProperty('guid')->setValue($chatRoom, $roomGuid);
        $this->chatRoomMockFactory->getProperty('roomType')->setValue($chatRoom, $roomType);

        if ($groupGuid) {
            $this->chatRoomMockFactory->getProperty('groupGuid')->setValue($chatRoom, $groupGuid);
        }

        return $chatRoom;
    }

    private function generateChatMessageMock(
        int $roomGuid,
        ChatMessageTypeEnum $messageType
    ): ChatMessage {
        $chatMessage = $this->chatMessageMockFactory->newInstanceWithoutConstructor();
        
        $this->chatMessageMockFactory->getProperty('roomGuid')->setValue($chatMessage, $roomGuid);
        $this->chatMessageMockFactory->getProperty('messageType')->setValue($chatMessage, $messageType);

        return $chatMessage;
    }
}
