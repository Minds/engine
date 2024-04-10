<?php

namespace Spec\Minds\Core\Chat\Services;

use DateTimeImmutable;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Events\Sockets\ChatEvent;
use Minds\Core\Chat\Events\Sockets\Enums\ChatEventTypeEnum;
use Minds\Core\Chat\Notifications\Events\ChatNotificationEvent;
use Minds\Core\Chat\Repositories\MessageRepository;
use Minds\Core\Chat\Repositories\RoomRepository;
use Minds\Core\Chat\Services\MessageService;
use Minds\Core\Chat\Services\ReceiptService;
use Minds\Core\Chat\Types\ChatMessageEdge;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\Topics\ChatNotificationsTopic;
use Minds\Core\Guid;
use Minds\Core\Sockets\Events as SocketEvents;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use ReflectionClass;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class MessageServiceSpec extends ObjectBehavior
{
    private Collaborator $messageRepositoryMock;
    private Collaborator $roomRepositoryMock;
    private Collaborator $receiptServiceMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $socketEventsMock;
    private Collaborator $chatNotificationsTopicMock;

    private ReflectionClass $chatMessageFactoryMock;

    public function let(
        MessageRepository $messageRepositoryMock,
        RoomRepository $roomRepositoryMock,
        ReceiptService $receiptServiceMock,
        EntitiesBuilder $entitiesBuilderMock,
        SocketEvents $socketEvents,
        ChatNotificationsTopic $chatNotificationsTopic
    ) {
        $this->beConstructedWith($messageRepositoryMock, $roomRepositoryMock, $receiptServiceMock, $entitiesBuilderMock, $socketEvents, $chatNotificationsTopic);
        $this->messageRepositoryMock = $messageRepositoryMock;
        $this->roomRepositoryMock  = $roomRepositoryMock;
        $this->receiptServiceMock = $receiptServiceMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->socketEventsMock = $socketEvents;
        $this->chatNotificationsTopicMock = $chatNotificationsTopic;

        $this->chatMessageFactoryMock = new ReflectionClass(ChatMessage::class);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MessageService::class);
    }

    public function it_should_add_a_message(
        User $userMock
    ): void {
        $userMock->getGuid()
            ->willReturn('123');

        $this->roomRepositoryMock->isUserMemberOfRoom(
            123,
            $userMock
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->messageRepositoryMock->beginTransaction()
            ->shouldBeCalled();

        $this->messageRepositoryMock->addMessage(Argument::type(ChatMessage::class))
            ->shouldBeCalled();

        $this->receiptServiceMock->updateReceipt(Argument::type(ChatMessage::class), $userMock)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->messageRepositoryMock->commitTransaction()
            ->shouldBeCalled();

        $this->socketEventsMock->setRoom('chat:123')
            ->shouldBeCalledOnce()
            ->willReturn($this->socketEventsMock);

        $this->socketEventsMock->emit(
            "chat:123",
            json_encode(new ChatEvent(
                type: ChatEventTypeEnum::NEW_MESSAGE,
                metadata: [
                    'senderGuid' => 123,
                ],
            ))
        )
            ->shouldBeCalledOnce();

        $this->chatNotificationsTopicMock->send(Argument::type(ChatNotificationEvent::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->addMessage(
            123,
            $userMock,
            'just for testing'
        )->shouldBeAnInstanceOf(ChatMessageEdge::class);
    }

    public function it_should_throw_exception_when_trying_to_store_empty_chat_message(
        User $userMock
    ): void {
        $userMock->getGuid()
            ->willReturn('123');

        $this
            ->shouldThrow(
                new GraphQLException(message: "Message cannot be empty", code: 400)
            )
            ->during(
                'addMessage',
                [
                    123,
                    $userMock,
                    ''
                ]
            );
    }

    public function it_should_throw_exception_when_trying_to_store_chat_message_as_NOT_ROOM_MEMBER(
        User $userMock
    ): void {
        $userMock->getGuid()
            ->willReturn('123');

        $this->roomRepositoryMock->isUserMemberOfRoom(123, $userMock)
            ->shouldBeCalled()
            ->willReturn(false);

        $this
            ->shouldThrow(
                new GraphQLException(message: "You are not a member of this room", code: 403)
            )
            ->during(
                'addMessage',
                [
                    123,
                    $userMock,
                    'test message'
                ]
            );
    }

    public function it_should_submit_a_read_receipt_when_sending_a_message(
        User $userMock
    ) {
        $roomGuid = (int) Guid::build();
        $userMock->getGuid()
            ->willReturn('123');

        $this->roomRepositoryMock->isUserMemberOfRoom($roomGuid, $userMock)
            ->shouldBeCalled()
            ->willReturn(true);
        
        $this->messageRepositoryMock->beginTransaction()
            ->shouldBeCalled();

        $this->messageRepositoryMock->addMessage(Argument::type(ChatMessage::class))
            ->shouldBeCalled();

        $this->messageRepositoryMock->commitTransaction()
            ->shouldBeCalled();

        $this->receiptServiceMock->updateReceipt(Argument::type(ChatMessage::class), $userMock)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->socketEventsMock->setRoom("chat:$roomGuid")
            ->shouldBeCalledOnce()
            ->willReturn($this->socketEventsMock);

        $this->socketEventsMock->emit(
            "chat:$roomGuid",
            json_encode(new ChatEvent(
                type: ChatEventTypeEnum::NEW_MESSAGE,
                metadata: [
                    'senderGuid' => 123,
                ],
            ))
        )
            ->shouldBeCalledOnce();

        $this->chatNotificationsTopicMock->send(Argument::type(ChatNotificationEvent::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $result = $this->addMessage(roomGuid: $roomGuid, user: $userMock, message: 'just for testing');
        $result->shouldBeAnInstanceOf(ChatMessageEdge::class);
    }

    public function it_should_get_chat_messages(
        User $userMock
    ): void {
        $this->roomRepositoryMock->isUserMemberOfRoom(
            123,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->messageRepositoryMock->getMessagesByRoom(
            123,
            12,
            null,
            null
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'messages' => [
                    $this->generateChatMessageMock(
                        messageGuid: 1,
                        senderGuid: 123
                    )
                ],
                'hasMore' => false
            ]);

        $this->entitiesBuilderMock->single(123)
            ->shouldBeCalledOnce()
            ->willReturn($userMock);

        $response = $this->getMessages(
            123,
            $userMock
        );

        $response->shouldBeArray();

        $response['edges'][0]->shouldBeAnInstanceOf(ChatMessageEdge::class);
        $response['edges'][0]->getNode()->chatMessage->guid->shouldEqual(1);
        $response['edges'][0]->getNode()->chatMessage->senderGuid->shouldEqual(123);
        $response['edges'][0]->getCursor()->shouldEqual(base64_encode('1'));
    }

    private function generateChatMessageMock(
        int $messageGuid,
        int $senderGuid
    ): ChatMessage {
        $chatMessageMock = $this->chatMessageFactoryMock->newInstanceWithoutConstructor();
        $this->chatMessageFactoryMock->getProperty('guid')->setValue($chatMessageMock, $messageGuid);
        $this->chatMessageFactoryMock->getProperty('senderGuid')->setValue($chatMessageMock, $senderGuid);
        $this->chatMessageFactoryMock->getProperty('createdAt')->setValue($chatMessageMock, new DateTimeImmutable());

        return $chatMessageMock;
    }

    public function it_should_get_chat_message_as_NON_ADMIN(
        User $userMock
    ): void {
        $this->roomRepositoryMock->isUserMemberOfRoom(
            123,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->messageRepositoryMock->getMessageByGuid(123, 1)
            ->shouldBeCalledOnce()
            ->willReturn($this->generateChatMessageMock(1, 123));

        $this->getMessage(
            123,
            1,
            $userMock,
            false
        )->shouldBeAnInstanceOf(ChatMessage::class);
    }

    public function it_should_get_chat_message_as_ADMIN(
        User $userMock
    ): void {
        $userMock->isAdmin()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->isUserMemberOfRoom(
            123,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->messageRepositoryMock->getMessageByGuid(123, 1)
            ->shouldBeCalledOnce()
            ->willReturn($this->generateChatMessageMock(1, 123));

        $this->getMessage(
            123,
            1,
            $userMock,
            false
        )->shouldBeAnInstanceOf(ChatMessage::class);
    }

    public function it_should_throw_exception_when_get_chat_message_and_user_not_room_member_and_not_admin(
        User $userMock
    ): void {
        $userMock->isAdmin()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->roomRepositoryMock->isUserMemberOfRoom(
            123,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->shouldThrow(
            new GraphQLException(message: "You are not a member of this room", code: 403)
        )->during(
            'getMessage',
            [
                123,
                1,
                $userMock,
                false
            ]
        );
    }

    public function it_should_delete_message(
        User $userMock
    ): void {
        $this->roomRepositoryMock->isUserMemberOfRoom(
            123,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->messageRepositoryMock->getMessageByGuid(123, 1)
            ->shouldBeCalledOnce()
            ->willReturn($this->generateChatMessageMock(1, 123));

        $userMock->isAdmin()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $userMock->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->messageRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->receiptServiceMock->deleteAllMessageReadReceipts(
            123,
            1
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->messageRepositoryMock->deleteChatMessage(
            123,
            1
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->messageRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->deleteMessage(
            123,
            1,
            $userMock
        )->shouldEqual(true);
    }
}
