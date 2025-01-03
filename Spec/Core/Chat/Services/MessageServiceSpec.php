<?php

namespace Spec\Minds\Core\Chat\Services;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Minds\Core\Chat\Delegates\AnalyticsDelegate;
use Minds\Core\Chat\Entities\ChatImage;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Entities\ChatRichEmbed;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Entities\ChatRoomListItem;
use Minds\Core\Chat\Enums\ChatMessageTypeEnum;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Events\Sockets\ChatEvent;
use Minds\Core\Chat\Events\Sockets\Enums\ChatEventTypeEnum;
use Minds\Core\Chat\Notifications\Events\ChatNotificationEvent;
use Minds\Core\Chat\Repositories\MessageRepository;
use Minds\Core\Chat\Repositories\RoomRepository;
use Minds\Core\Chat\Services\ChatImageProcessorService;
use Minds\Core\Chat\Services\ChatImageStorageService;
use Minds\Core\Chat\Services\MessageService;
use Minds\Core\Chat\Services\ReceiptService;
use Minds\Core\Chat\Services\RichEmbedService;
use Minds\Core\Chat\Types\ChatMessageEdge;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\Topics\ChatNotificationsTopic;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Security\ACL;
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
    private Collaborator $imageStorageServiceMock;
    private Collaborator $imageProcessorServiceMock;
    private Collaborator $receiptServiceMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $socketEventsMock;
    private Collaborator $chatNotificationsTopicMock;
    private Collaborator $chatRichEmbedServiceMock;
    private Collaborator $analyticsDelegateMock;
    private Collaborator $aclMock;
    private Collaborator $loggerMock;

    private ReflectionClass $chatMessageFactoryMock;
    private ReflectionClass $chatRichEmbedFactoryMock;
    private ReflectionClass $chatRoomFactoryMock;
    private ReflectionClass $chatRoomListItemFactoryMock;
    private ReflectionClass $chatImageFactoryMock;

    public function let(
        MessageRepository $messageRepositoryMock,
        RoomRepository $roomRepositoryMock,
        ChatImageStorageService $imageStorageServiceMock,
        ChatImageProcessorService $imageProcessorServiceMock,
        ReceiptService $receiptServiceMock,
        EntitiesBuilder $entitiesBuilderMock,
        SocketEvents $socketEvents,
        ChatNotificationsTopic $chatNotificationsTopic,
        RichEmbedService $chatRichEmbedService,
        AnalyticsDelegate $analyticsDelegate,
        ACL $acl,
        Logger $logger
    ) {
        $this->beConstructedWith($messageRepositoryMock, $roomRepositoryMock, $imageStorageServiceMock, $imageProcessorServiceMock, $receiptServiceMock, $entitiesBuilderMock, $socketEvents, $chatNotificationsTopic, $chatRichEmbedService, $analyticsDelegate, $acl, $logger);
        $this->messageRepositoryMock = $messageRepositoryMock;
        $this->roomRepositoryMock  = $roomRepositoryMock;
        $this->imageStorageServiceMock = $imageStorageServiceMock;
        $this->imageProcessorServiceMock = $imageProcessorServiceMock;
        $this->receiptServiceMock = $receiptServiceMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->socketEventsMock = $socketEvents;
        $this->chatNotificationsTopicMock = $chatNotificationsTopic;
        $this->chatRichEmbedServiceMock = $chatRichEmbedService;
        $this->analyticsDelegateMock = $analyticsDelegate;
        $this->aclMock = $acl;
        $this->loggerMock = $logger;

        $this->chatMessageFactoryMock = new ReflectionClass(ChatMessage::class);
        $this->chatRichEmbedFactoryMock = new ReflectionClass(ChatRichEmbed::class);
        $this->chatRoomFactoryMock = new ReflectionClass(ChatRoom::class);
        $this->chatRoomListItemFactoryMock = new ReflectionClass(ChatRoomListItem::class);
        $this->chatImageFactoryMock = new ReflectionClass(ChatImage::class);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MessageService::class);
    }

    public function it_should_add_a_message(
        User $userMock
    ): void {
        $plainText = 'just for testing';
        $chatRoom = $this->generateChatRoomMock();
        $listItemMock = $this->generateChatRoomListItemMock(
            $chatRoom
        );

        $this->aclMock->write($chatRoom, $userMock)
            ->shouldBeCalled()
            ->willReturn(true);

        $userMock->getGuid()
            ->willReturn('123');

        $this->chatRichEmbedServiceMock->parseFromText($plainText)
            ->shouldBeCalled()
            ->willReturn(null);

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
                    'senderGuid' => "123",
                ],
            ))
        )
            ->shouldBeCalledOnce();

        $this->chatNotificationsTopicMock->send(Argument::type(ChatNotificationEvent::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            123
        )->shouldBeCalled()->willReturn([
            'chatRooms' => [ $listItemMock ]
        ]);

        $this->analyticsDelegateMock->onMessageSend(
            actor: $userMock,
            message: Argument::type(ChatMessage::class),
            chatRoom: $chatRoom
        )->shouldBeCalled();

        $this->addMessage(
            123,
            $userMock,
            $plainText
        )->shouldBeAnInstanceOf(ChatMessageEdge::class);
    }

    public function it_should_add_a_message_with_a_rich_embed(
        User $userMock
    ): void {
        $plainText = 'just for testing www.minds.com';
        $chatRichEmbed = $this->generateChatRichEmbedMock();
        $chatRoom = $this->generateChatRoomMock(guid: 123);
        $listItemMock = $this->generateChatRoomListItemMock(
            $chatRoom
        );

        $userMock->getGuid()
            ->willReturn('123');

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            123
        )->shouldBeCalled()->willReturn([
            'chatRooms' => [ $listItemMock ]
        ]);

        $this->aclMock->write(Argument::any(), $userMock)
            ->shouldBeCalled()
            ->willReturn(true);
    
        $this->chatRichEmbedServiceMock->parseFromText($plainText)
            ->shouldBeCalled()
            ->willReturn($chatRichEmbed);

        $this->messageRepositoryMock->beginTransaction()
            ->shouldBeCalled();

        $this->messageRepositoryMock->addMessage(Argument::type(ChatMessage::class))
            ->shouldBeCalled();

        $this->messageRepositoryMock->addRichEmbed(
            roomGuid: 123,
            messageGuid: Argument::type('int'),
            chatRichEmbed: $chatRichEmbed
        )
            ->shouldBeCalled()
            ->willReturn(true);

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
                    'senderGuid' => "123",
                ],
            ))
        )
            ->shouldBeCalledOnce();

        $this->chatNotificationsTopicMock->send(Argument::type(ChatNotificationEvent::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $chatRoom = $this->generateChatRoomMock();

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            123
        )->shouldBeCalled()->willReturn([
            'chatRooms' => [
                $this->generateChatRoomListItemMock(
                    $chatRoom
                )
            ]
        ]);

        $this->analyticsDelegateMock->onMessageSend(
            actor: $userMock,
            message: Argument::type(ChatMessage::class),
            chatRoom: $chatRoom
        )->shouldBeCalled();

        $this->addMessage(
            123,
            $userMock,
            $plainText
        )->shouldBeAnInstanceOf(ChatMessageEdge::class);
    }

    public function it_should_add_a_message_with_an_image(
        User $userMock,
        ChatImage $chatImageMock,
    ): void {
        $plainText = 'just for testing www.minds.com';
        $imageBlob = 'imageBlob';
        $chatRoom = $this->generateChatRoomMock(guid: 123);
        $listItemMock = $this->generateChatRoomListItemMock(
            $chatRoom
        );
        $chatImageMock = $this->generateChatImageMock(
            guid: 123,
            roomGuid: 123,
            messageGuid: 123
        );

        $userMock->getGuid()
            ->willReturn('123');

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            123
        )->shouldBeCalled()->willReturn([
            'chatRooms' => [ $listItemMock ]
        ]);

        $this->imageProcessorServiceMock->process(
            user: $userMock,
            imageBlob: $imageBlob,
            roomGuid: 123,
            messageGuid: Argument::type('int')
        )->shouldBeCalled()->willReturn($chatImageMock);

        $this->aclMock->write(Argument::any(), $userMock)
            ->shouldBeCalled()
            ->willReturn(true);
    
        $this->messageRepositoryMock->beginTransaction()
            ->shouldBeCalled();

        $this->messageRepositoryMock->addMessage(Argument::type(ChatMessage::class))
            ->shouldBeCalled();

        $this->messageRepositoryMock->addImage($chatImageMock)
            ->shouldBeCalled()
            ->willReturn(true);

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
                    'senderGuid' => "123",
                ],
            ))
        )
            ->shouldBeCalledOnce();

        $this->chatNotificationsTopicMock->send(Argument::type(ChatNotificationEvent::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $chatRoom = $this->generateChatRoomMock();

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            123
        )->shouldBeCalled()->willReturn([
            'chatRooms' => [
                $this->generateChatRoomListItemMock(
                    $chatRoom
                )
            ]
        ]);

        $this->analyticsDelegateMock->onMessageSend(
            actor: $userMock,
            message: Argument::type(ChatMessage::class),
            chatRoom: $chatRoom
        )->shouldBeCalled();

        $this->addMessage(
            123,
            $userMock,
            $plainText,
            $imageBlob
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
        $chatRoom = $this->generateChatRoomMock(guid: 123);
        $listItemMock = $this->generateChatRoomListItemMock(
            $chatRoom
        );

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            123
        )->shouldBeCalled()->willReturn([
            'chatRooms' => [ $listItemMock ]
        ]);

        $this
            ->shouldThrow(
                new GraphQLException(message: "You cannot add a message to this room", code: 403)
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
        $roomGuid = 1234567890123456;
        $chatRoom = $this->generateChatRoomMock(guid: $roomGuid);
        $listItemMock = $this->generateChatRoomListItemMock(
            $chatRoom
        );

        $userMock->getGuid()
            ->willReturn('123');

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            $roomGuid
        )->shouldBeCalled()->willReturn([
            'chatRooms' => [ $listItemMock ]
        ]);

        $this->aclMock->write(Argument::any(), $userMock)
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
                    'senderGuid' => "123",
                ],
            ))
        )
            ->shouldBeCalledOnce();

        $this->chatNotificationsTopicMock->send(Argument::type(ChatNotificationEvent::class))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $chatRoom = $this->generateChatRoomMock();

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            $roomGuid
        )->shouldBeCalled()->willReturn([
            'chatRooms' => [
                $this->generateChatRoomListItemMock(
                    $chatRoom
                )
            ]
        ]);

        $this->analyticsDelegateMock->onMessageSend(
            actor: $userMock,
            message: Argument::type(ChatMessage::class),
            chatRoom: $chatRoom
        )->shouldBeCalled();

        $result = $this->addMessage(roomGuid: $roomGuid, user: $userMock, message: 'just for testing');
        $result->shouldBeAnInstanceOf(ChatMessageEdge::class);
    }

    public function it_should_get_chat_messages(
        User $userMock
    ): void {
        $chatRoom = $this->generateChatRoomMock();
        $chatRoomListItemMock = $this->generateChatRoomListItemMock($chatRoom);

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

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            123
        )
            ->shouldBeCalled()
            ->willReturn(['chatRooms' => [$chatRoomListItemMock]]);

        $this->aclMock->write($chatRoomListItemMock->chatRoom, $userMock)
            ->shouldBeCalled()
            ->willReturn(true);
    
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

    public function it_should_NOT_get_chat_messages_for_a_user_when_acl_check_fails(
        User $userMock
    ): void {
        $chatRoom = $this->generateChatRoomMock();
        $chatRoomListItemMock = $this->generateChatRoomListItemMock($chatRoom);

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

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            123
        )
            ->shouldBeCalled()
            ->willReturn(['chatRooms' => [$chatRoomListItemMock]]);

        $this->aclMock->write($chatRoomListItemMock->chatRoom, $userMock)
            ->shouldBeCalled()
            ->willReturn(false);
    
        $response = $this->getMessages(
            123,
            $userMock
        );

        $response->shouldBeArray();
        $response->shouldBe([
            'edges' => [],
            'hasMore' => false
        ]);
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

    public function it_should_delete_message_when_sender(
        User $userMock
    ): void {
        $chatRoom = $this->generateChatRoomMock();
        $chatRoomListItemMock = $this->generateChatRoomListItemMock($chatRoom);

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

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            123
        )
            ->shouldBeCalled()
            ->willReturn(['chatRooms' => [$chatRoomListItemMock]]);

        $this->roomRepositoryMock->isUserRoomOwner(
            roomGuid: 123,
            user: $userMock
        )
            ->shouldBeCalled()
            ->willReturn(false);

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

        $this->socketEventsMock->setRoom("chat:123")
            ->shouldBeCalledOnce()
            ->willReturn($this->socketEventsMock);

        $this->socketEventsMock->emit(
            "chat:123",
            json_encode(new ChatEvent(
                type: ChatEventTypeEnum::MESSAGE_DELETED,
                metadata: [
                    'messageGuid' => "1",
                ],
            ))
        )
            ->shouldBeCalledOnce();

        $this->deleteMessage(
            123,
            1,
            $userMock
        )->shouldEqual(true);
    }

    public function it_should_delete_message_when_admin(
        User $userMock
    ): void {
        $chatRoom = $this->generateChatRoomMock(roomType: ChatRoomTypeEnum::ONE_TO_ONE);
        $chatRoomListItemMock = $this->generateChatRoomListItemMock($chatRoom);

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
            ->willReturn($this->generateChatMessageMock(1, 234));

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            123
        )
            ->shouldBeCalled()
            ->willReturn(['chatRooms' => [$chatRoomListItemMock]]);

        $this->roomRepositoryMock->isUserRoomOwner(
            roomGuid: 123,
            user: $userMock
        )
            ->shouldBeCalled()
            ->willReturn(false);

        $userMock->isAdmin()
            ->shouldBeCalledOnce()
            ->willReturn(true);

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

        $this->socketEventsMock->setRoom("chat:123")
            ->shouldBeCalledOnce()
            ->willReturn($this->socketEventsMock);

        $this->socketEventsMock->emit(
            "chat:123",
            json_encode(new ChatEvent(
                type: ChatEventTypeEnum::MESSAGE_DELETED,
                metadata: [
                    'messageGuid' => "1",
                ],
            ))
        )
            ->shouldBeCalledOnce();

        $this->deleteMessage(
            123,
            1,
            $userMock
        )->shouldEqual(true);
    }

    public function it_should_delete_message_when_group_owner(
        User $userMock
    ): void {
        $chatRoom = $this->generateChatRoomMock(roomType: ChatRoomTypeEnum::GROUP_OWNED);
        $chatRoomListItemMock = $this->generateChatRoomListItemMock($chatRoom);

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
            ->willReturn($this->generateChatMessageMock(1, 234));

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            123
        )
            ->shouldBeCalled()
            ->willReturn(['chatRooms' => [$chatRoomListItemMock]]);

        $this->roomRepositoryMock->isUserRoomOwner(
            roomGuid: 123,
            user: $userMock
        )
            ->shouldBeCalled()
            ->willReturn(true);

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

        $this->socketEventsMock->setRoom("chat:123")
            ->shouldBeCalledOnce()
            ->willReturn($this->socketEventsMock);

        $this->socketEventsMock->emit(
            "chat:123",
            json_encode(new ChatEvent(
                type: ChatEventTypeEnum::MESSAGE_DELETED,
                metadata: [
                    'messageGuid' => "1",
                ],
            ))
        )
            ->shouldBeCalledOnce();

        $this->deleteMessage(
            123,
            1,
            $userMock
        )->shouldEqual(true);
    }

    public function it_should_not_delete_message_when_the_user_has_no_permission(
        User $userMock
    ): void {
        $chatRoom = $this->generateChatRoomMock(roomType: ChatRoomTypeEnum::ONE_TO_ONE);
        $chatRoomListItemMock = $this->generateChatRoomListItemMock($chatRoom);

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
            ->willReturn($this->generateChatMessageMock(1, 234));

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            123
        )
            ->shouldBeCalled()
            ->willReturn(['chatRooms' => [$chatRoomListItemMock]]);

        $this->roomRepositoryMock->isUserRoomOwner(
            roomGuid: 123,
            user: $userMock
        )
            ->shouldBeCalled()
            ->willReturn(false);

        $userMock->isAdmin()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $userMock->getGuid()
            ->shouldBeCalledOnce()
            ->willReturn('123');

        $this->messageRepositoryMock->beginTransaction()
            ->shouldNotBeCalled();

        $this->shouldThrow(new GraphQLException("You are not allowed to delete this message", 403))->duringDeleteMessage(
            123,
            1,
            $userMock
        );
    }

    public function it_should_delete_message_with_a_rich_embed(
        User $userMock
    ): void {
        $chatRoom = $this->generateChatRoomMock(roomType: ChatRoomTypeEnum::ONE_TO_ONE);
        $chatRoomListItemMock = $this->generateChatRoomListItemMock($chatRoom);

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
            ->willReturn($this->generateChatMessageMock(1, 123, ChatMessageTypeEnum::RICH_EMBED));

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            123
        )
            ->shouldBeCalled()
            ->willReturn(['chatRooms' => [$chatRoomListItemMock]]);

        $this->roomRepositoryMock->isUserRoomOwner(
            roomGuid: 123,
            user: $userMock
        )
            ->shouldBeCalled()
            ->willReturn(false);

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

        $this->messageRepositoryMock->deleteRichEmbed(123, 1)
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

        $this->socketEventsMock->setRoom("chat:123")
            ->shouldBeCalledOnce()
            ->willReturn($this->socketEventsMock);

        $this->socketEventsMock->emit(
            "chat:123",
            json_encode(new ChatEvent(
                type: ChatEventTypeEnum::MESSAGE_DELETED,
                metadata: [
                    'messageGuid' => "1",
                ],
            ))
        )
            ->shouldBeCalledOnce();

        $this->deleteMessage(
            123,
            1,
            $userMock
        )->shouldEqual(true);
    }

    public function it_should_delete_message_with_an_image(
        User $userMock
    ): void {
        $chatRoom = $this->generateChatRoomMock(roomType: ChatRoomTypeEnum::ONE_TO_ONE);
        $chatRoomListItemMock = $this->generateChatRoomListItemMock($chatRoom);

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

        $chatImageMock = $this->generateChatImageMock(123, 123, 1);

        $this->messageRepositoryMock->getMessageByGuid(123, 1)
            ->shouldBeCalledOnce()
            ->willReturn($this->generateChatMessageMock(1, 123, ChatMessageTypeEnum::IMAGE, $chatImageMock));

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            1,
            null,
            null,
            123
        )
            ->shouldBeCalled()
            ->willReturn(['chatRooms' => [$chatRoomListItemMock]]);

        $this->roomRepositoryMock->isUserRoomOwner(
            roomGuid: 123,
            user: $userMock
        )
            ->shouldBeCalled()
            ->willReturn(false);

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

        $this->imageStorageServiceMock->delete(
            imageGuid: (string) $chatImageMock->guid,
            ownerGuid: $chatImageMock->roomGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->messageRepositoryMock->deleteImage(123, 1)
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

        $this->socketEventsMock->setRoom("chat:123")
            ->shouldBeCalledOnce()
            ->willReturn($this->socketEventsMock);

        $this->socketEventsMock->emit(
            "chat:123",
            json_encode(new ChatEvent(
                type: ChatEventTypeEnum::MESSAGE_DELETED,
                metadata: [
                    'messageGuid' => "1",
                ],
            ))
        )
            ->shouldBeCalledOnce();

        $this->deleteMessage(
            123,
            1,
            $userMock
        )->shouldEqual(true);
    }

    private function generateChatMessageMock(
        int $messageGuid,
        int $senderGuid,
        ChatMessageTypeEnum $messageType = ChatMessageTypeEnum::TEXT,
        ChatImage $chatImageMock = null
    ): ChatMessage {
        $chatMessageMock = $this->chatMessageFactoryMock->newInstanceWithoutConstructor();
        $this->chatMessageFactoryMock->getProperty('guid')->setValue($chatMessageMock, $messageGuid);
        $this->chatMessageFactoryMock->getProperty('senderGuid')->setValue($chatMessageMock, $senderGuid);
        $this->chatMessageFactoryMock->getProperty('createdAt')->setValue($chatMessageMock, new DateTimeImmutable());
        $this->chatMessageFactoryMock->getProperty('messageType')->setValue($chatMessageMock, $messageType);
        $this->chatMessageFactoryMock->getProperty('image')->setValue($chatMessageMock, $chatImageMock);

        return $chatMessageMock;
    }

    private function generateChatRichEmbedMock(
        string $url = 'example.minds.com',
        string $canonicalUrl = 'https://example.minds.com',
        string $title = 'title',
        string $description = 'description',
        string $author = 'author',
        string $thumbnailSrc = 'https://example.minds.com/img/thumbnail.png',
        DateTimeInterface $createdTimestamp = new DateTime(),
        DateTimeInterface $updatedTimestamp = new DateTime()
    ): ChatRichEmbed {
        $chatRichEmbedMock = $this->chatRichEmbedFactoryMock->newInstanceWithoutConstructor();
        $this->chatRichEmbedFactoryMock->getProperty('url')->setValue($chatRichEmbedMock, $url);
        $this->chatRichEmbedFactoryMock->getProperty('canonicalUrl')->setValue($chatRichEmbedMock, $canonicalUrl);
        $this->chatRichEmbedFactoryMock->getProperty('title')->setValue($chatRichEmbedMock, $title);
        $this->chatRichEmbedFactoryMock->getProperty('description')->setValue($chatRichEmbedMock, $description);
        $this->chatRichEmbedFactoryMock->getProperty('author')->setValue($chatRichEmbedMock, $author);
        $this->chatRichEmbedFactoryMock->getProperty('thumbnailSrc')->setValue($chatRichEmbedMock, $thumbnailSrc);
        $this->chatRichEmbedFactoryMock->getProperty('createdTimestamp')->setValue($chatRichEmbedMock, $createdTimestamp);
        $this->chatRichEmbedFactoryMock->getProperty('updatedTimestamp')->setValue($chatRichEmbedMock, $updatedTimestamp);

        return $chatRichEmbedMock;
    }

    private function generateChatRoomListItemMock(ChatRoom $chatRoom): ChatRoomListItem
    {
        $chatRoomListItem = $this->chatRoomListItemFactoryMock->newInstanceWithoutConstructor();

        $this->chatRoomListItemFactoryMock->getProperty('chatRoom')->setValue($chatRoomListItem, $chatRoom);

        return $chatRoomListItem;
    }

    private function generateChatRoomMock(
        $guid = null,
        $roomType = ChatRoomTypeEnum::ONE_TO_ONE,
    ): ChatRoom {
        $chatRoom = $this->chatRoomFactoryMock->newInstanceWithoutConstructor();

        $this->chatRoomFactoryMock->getProperty('guid')->setValue($chatRoom, $guid ?? Guid::build());
        $this->chatRoomFactoryMock->getProperty('roomType')->setValue($chatRoom, $roomType);

        return $chatRoom;
    }

    private function generateChatImageMock(
        int $guid,
        int $roomGuid,
        int $messageGuid,
        int $width = 100,
        int $height = 100,
        string $blurhash = 'blurhash',
    ): ChatImage {
        $chatImageMock = $this->chatImageFactoryMock->newInstanceWithoutConstructor();

        $this->chatImageFactoryMock->getProperty('guid')->setValue($chatImageMock, $guid);
        $this->chatImageFactoryMock->getProperty('roomGuid')->setValue($chatImageMock, $roomGuid);
        $this->chatImageFactoryMock->getProperty('messageGuid')->setValue($chatImageMock, $messageGuid);
        $this->chatImageFactoryMock->getProperty('width')->setValue($chatImageMock, $width);
        $this->chatImageFactoryMock->getProperty('height')->setValue($chatImageMock, $height);
        $this->chatImageFactoryMock->getProperty('blurhash')->setValue($chatImageMock, $blurhash);

        return $chatImageMock;
    }
}
