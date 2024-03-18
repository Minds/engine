<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Chat\Services;

use DateTimeImmutable;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Entities\ChatRoomListItem;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomRoleEnum;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Exceptions\ChatRoomNotFoundException;
use Minds\Core\Chat\Exceptions\InvalidChatRoomTypeException;
use Minds\Core\Chat\Repositories\RoomRepository;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Chat\Types\ChatRoomEdge;
use Minds\Core\Chat\Types\ChatRoomMemberEdge;
use Minds\Core\Chat\Types\ChatRoomNode;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\Block\Manager as BlockManager;
use Minds\Core\Subscriptions\Relational\Repository as SubscriptionsRepository;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use ReflectionClass;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class RoomServiceSpec extends ObjectBehavior
{
    private Collaborator $roomRepositoryMock;
    private Collaborator $subscriptionsRepositoryMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $blockManagerMock;

    private ReflectionClass $chatRoomMockFactory;
    private ReflectionClass $chatRoomListItemMockFactory;

    public function let(
        RoomRepository $roomRepository,
        SubscriptionsRepository $subscriptionsRepository,
        EntitiesBuilder $entitiesBuilder,
        BlockManager $blockManager
    ): void {
        $this->roomRepositoryMock = $roomRepository;
        $this->subscriptionsRepositoryMock = $subscriptionsRepository;
        $this->entitiesBuilderMock = $entitiesBuilder;
        $this->blockManagerMock = $blockManager;
        $this->beConstructedWith(
            $this->roomRepositoryMock,
            $this->subscriptionsRepositoryMock,
            $this->entitiesBuilderMock,
            $this->blockManagerMock
        );

        $this->chatRoomMockFactory = new ReflectionClass(ChatRoom::class);
        $this->chatRoomListItemMockFactory = new ReflectionClass(ChatRoomListItem::class);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(RoomService::class);
    }

    public function it_should_create_chat_room_NO_ROOM_TYPE_PROVIDED_NO_EXISTING_ROOM_and_SUBSCRIBING_USER(
        User $user
    ): void {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->roomRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->roomRepositoryMock->getOneToOneRoomByMembers(
            123,
            456
        )
            ->shouldBeCalledOnce()
            ->willThrow(ChatRoomNotFoundException::class);

        $this->roomRepositoryMock->createRoom(
            Argument::type('integer'),
            ChatRoomTypeEnum::ONE_TO_ONE,
            123,
            Argument::type(DateTimeImmutable::class)
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->addRoomMember(
            Argument::type('integer'),
            '123',
            ChatRoomMemberStatusEnum::ACTIVE,
            ChatRoomRoleEnum::OWNER
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->subscriptionsRepositoryMock->isSubscribed(
            456,
            123
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->addRoomMember(
            Argument::type('integer'),
            456,
            ChatRoomMemberStatusEnum::ACTIVE,
            ChatRoomRoleEnum::OWNER
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->createRoom(
            $user,
            ["456"],
            null
        )
            ->shouldBeAnInstanceOf(ChatRoomEdge::class);
    }

    public function it_should_create_chat_room_NO_ROOM_TYPE_PROVIDED_NO_EXISTING_ROOM_and_NOT_SUBSCRIBING_USER(
        User $user
    ): void {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->roomRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->roomRepositoryMock->getOneToOneRoomByMembers(
            123,
            456
        )
            ->shouldBeCalledOnce()
            ->willThrow(ChatRoomNotFoundException::class);

        $this->roomRepositoryMock->createRoom(
            Argument::type('integer'),
            ChatRoomTypeEnum::ONE_TO_ONE,
            123,
            Argument::type(DateTimeImmutable::class)
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->addRoomMember(
            Argument::type('integer'),
            '123',
            ChatRoomMemberStatusEnum::ACTIVE,
            ChatRoomRoleEnum::OWNER
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->subscriptionsRepositoryMock->isSubscribed(
            456,
            123
        )
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->roomRepositoryMock->addRoomMember(
            Argument::type('integer'),
            456,
            ChatRoomMemberStatusEnum::INVITE_PENDING,
            ChatRoomRoleEnum::OWNER
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->createRoom(
            $user,
            ["456"],
            null
        )
            ->shouldBeAnInstanceOf(ChatRoomEdge::class);
    }

    public function it_should_create_chat_room_WITH_ROOM_TYPE_PROVIDED_NO_EXISTING_ROOM_and_NO_SUBSCRIBING_USER(
        User $user
    ): void {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->roomRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->roomRepositoryMock->getOneToOneRoomByMembers(
            123,
            456
        )
            ->shouldBeCalledOnce()
            ->willThrow(ChatRoomNotFoundException::class);

        $this->roomRepositoryMock->createRoom(
            Argument::type('integer'),
            ChatRoomTypeEnum::ONE_TO_ONE,
            123,
            Argument::type(DateTimeImmutable::class)
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->addRoomMember(
            Argument::type('integer'),
            '123',
            ChatRoomMemberStatusEnum::ACTIVE,
            ChatRoomRoleEnum::OWNER
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->subscriptionsRepositoryMock->isSubscribed(
            456,
            123
        )
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->roomRepositoryMock->addRoomMember(
            Argument::type('integer'),
            456,
            ChatRoomMemberStatusEnum::INVITE_PENDING,
            ChatRoomRoleEnum::OWNER
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->createRoom(
            $user,
            ["456"],
            ChatRoomTypeEnum::ONE_TO_ONE
        )
            ->shouldBeAnInstanceOf(ChatRoomEdge::class);
    }

    public function it_should_create_chat_room_WITH_EXISTING_ONE_TO_ONE_ROOM(
        User $user,
        ChatRoom $chatRoomMock
    ): void {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->roomRepositoryMock->beginTransaction()
            ->shouldNotBeCalled();

        $this->roomRepositoryMock->getOneToOneRoomByMembers(
            123,
            456
        )
            ->shouldBeCalledOnce()
            ->willReturn($chatRoomMock);

        $this->createRoom(
            $user,
            ["456"],
            ChatRoomTypeEnum::ONE_TO_ONE
        )
            ->shouldBeAnInstanceOf(ChatRoomEdge::class);
    }

    public function it_should_THROW_invalid_room_type_exception_when_trying_to_create_room_as_GROUP_OWNED(
        User $userMock
    ): void {
        $this
            ->shouldThrow(InvalidChatRoomTypeException::class)
            ->during(
                method: 'createRoom',
                arguments: [
                    $userMock,
                    [],
                    ChatRoomTypeEnum::GROUP_OWNED
                ]
            );
    }

    public function it_should_create_multi_user_chat_room(
        User $userMock
    ): void {
        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->roomRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->roomRepositoryMock->getOneToOneRoomByMembers(
            123,
            Argument::type('integer')
        )
            ->shouldNotBeCalled();

        $this->roomRepositoryMock->createRoom(
            Argument::type('integer'),
            ChatRoomTypeEnum::MULTI_USER,
            123,
            Argument::type(DateTimeImmutable::class)
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->addRoomMember(
            Argument::type('integer'),
            '123',
            ChatRoomMemberStatusEnum::ACTIVE,
            ChatRoomRoleEnum::OWNER
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->subscriptionsRepositoryMock->isSubscribed(
            456,
            123
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->subscriptionsRepositoryMock->isSubscribed(
            789,
            123
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->addRoomMember(
            Argument::type('integer'),
            456,
            ChatRoomMemberStatusEnum::ACTIVE,
            ChatRoomRoleEnum::MEMBER
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->addRoomMember(
            Argument::type('integer'),
            789,
            ChatRoomMemberStatusEnum::ACTIVE,
            ChatRoomRoleEnum::MEMBER
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->createRoom(
            $userMock,
            ["456", "789"],
            ChatRoomTypeEnum::MULTI_USER
        )
            ->shouldBeAnInstanceOf(ChatRoomEdge::class);
    }

    public function it_should_get_rooms_by_member(
        User $userMock
    ): void {
        $chatRoomListItemMock = $this->generateChatRoomListItem(
            chatRoom: $this->generateChatRoomMock(),
            lastMessagePlainText: null,
            lastMessageCreatedTimestamp: null
        );


        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [ChatRoomMemberStatusEnum::ACTIVE->name],
            12,
            null,
            null
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'chatRooms' => [
                    $chatRoomListItemMock
                ],
                'hasMore' => false
            ]);

        /**
         * @var ChatRoomEdge $chatRoomEdge
         */
        $chatRoomEdge = $this->getRoomsByMember(
            $userMock,
            12,
            null
        )['edges'][0];

        $chatRoomEdge->getNode()->shouldBeAnInstanceOf(ChatRoomNode::class);
        $chatRoomEdge->getCursor()->shouldEqual(base64_encode("0:" . $chatRoomListItemMock->chatRoom->createdAt->getTimestamp()));
    }

    public function it_should_get_rooms_by_member_WITH_LAST_MESSAGE(
        User $userMock
    ): void {
        $chatRoomListItemMock = $this->generateChatRoomListItem(
            chatRoom: $this->generateChatRoomMock(),
            lastMessagePlainText: 'sample message',
            lastMessageCreatedTimestamp: time()
        );


        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [ChatRoomMemberStatusEnum::ACTIVE->name],
            12,
            null,
            null
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'chatRooms' => [
                    $chatRoomListItemMock
                ],
                'hasMore' => false
            ]);

        /**
         * @var ChatRoomEdge $chatRoomEdge
         */
        $chatRoomEdge = $this->getRoomsByMember(
            $userMock,
            12,
            null
        )['edges'][0];

        $chatRoomEdge->getNode()->shouldBeAnInstanceOf(ChatRoomNode::class);
        $chatRoomEdge->getCursor()->shouldEqual(base64_encode((string)$chatRoomListItemMock->lastMessageCreatedTimestamp));
    }

    private function generateChatRoomMock(): ChatRoom
    {
        $chatRoom = $this->chatRoomMockFactory->newInstanceWithoutConstructor();
        $this->chatRoomMockFactory->getProperty('createdAt')->setValue($chatRoom, new DateTimeImmutable());

        return $chatRoom;
    }

    private function generateChatRoomListItem(
        ChatRoom $chatRoom,
        string|null $lastMessagePlainText = null,
        int|null $lastMessageCreatedTimestamp = null,
    ): ChatRoomListItem {
        $chatRoomListItem = $this->chatRoomListItemMockFactory->newInstanceWithoutConstructor();
        $this->chatRoomListItemMockFactory->getProperty('chatRoom')->setValue($chatRoomListItem, $chatRoom);
        $this->chatRoomListItemMockFactory->getProperty('lastMessagePlainText')->setValue($chatRoomListItem, $lastMessagePlainText);
        $this->chatRoomListItemMockFactory->getProperty('lastMessageCreatedTimestamp')->setValue($chatRoomListItem, $lastMessageCreatedTimestamp);

        return $chatRoomListItem;
    }

    public function it_should_get_room_total_members(): void
    {
        $this->roomRepositoryMock->getRoomTotalMembers(123)
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->getRoomTotalMembers(123)
            ->shouldEqual(1);
    }

    public function it_should_get_room_members(
        User $userMock,
        User $memberMock
    ): void {
        $memberMock->getGuid()
            ->willReturn('456');
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

        $this->roomRepositoryMock->getRoomMembers(
            123,
            $userMock,
            12,
            null
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'members' => [
                    [
                        'member_guid' => 456,
                        'joined_timestamp'
                    ]
                ],
                'hasMore' => false
            ]);

        $this->entitiesBuilderMock->single(456)
            ->shouldBeCalledOnce()
            ->willReturn(
                $memberMock
            );

        $results = $this->getRoomMembers(
            123,
            $userMock,
            null,
            null
        );

        /**
         * @var ChatRoomMemberEdge $chatRoomMemberEdge
         */
        $chatRoomMemberEdge = $results['edges'][0];

        $chatRoomMemberEdge->getNode()->getGuid()->shouldEqual('456');
    }

    public function it_should_throw_exception_when_get_room_total_members_and_USER_IS_NOT_MEMBER_OF_ROOM(
        User $userMock
    ): void {
        $this->roomRepositoryMock->isUserMemberOfRoom(
            123,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name,
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->shouldThrow(new GraphQLException(message: "You are not a member of this chat.", code: 403))
            ->during(
                method: 'getRoomMembers',
                arguments: [
                    123,
                    $userMock,
                    null,
                    null
                ]
            );
    }

    public function it_should_get_room(
        User $userMock
    ): void {
        $this->roomRepositoryMock->isUserMemberOfRoom(
            123,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name,
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $chatRoomListItemMock = $this->generateChatRoomListItem(
            chatRoom: $this->generateChatRoomMock(),
            lastMessagePlainText: null,
            lastMessageCreatedTimestamp: null
        );

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name,
            ],
            1,
            null,
            123,
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'chatRooms' => [
                    $chatRoomListItemMock
                ],
                'hasMore' => false
            ]);

        $this->roomRepositoryMock->getUserStatusInRoom(
            $userMock,
            123
        )
            ->shouldBeCalledOnce()
            ->willReturn(
                ChatRoomMemberStatusEnum::INVITE_PENDING
            );

        /**
         * @var ChatRoomEdge $chatRoomEdge
         */
        $chatRoomEdge = $this->getRoom(
            123,
            $userMock
        );

        $chatRoomEdge->getNode()->chatRoom->createdAt->shouldEqual($chatRoomListItemMock->chatRoom->createdAt);
        $chatRoomEdge->getNode()->isChatRequest->shouldBe(true);
    }

















}
