<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Chat\Services;

use DateTimeImmutable;
use Minds\Core\Chat\Delegates\AnalyticsDelegate;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Entities\ChatRoomListItem;
use Minds\Core\Chat\Enums\ChatRoomInviteRequestActionEnum;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomNotificationStatusEnum;
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
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipManager;
use Minds\Core\Groups\V2\Membership\Membership;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\Block\BlockEntry;
use Minds\Core\Security\Block\Manager as BlockManager;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\Subscriptions\Relational\Repository as SubscriptionsRepository;
use Minds\Entities\Group;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use ReflectionClass;
use Spec\Minds\Common\Traits\CommonMatchers;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class RoomServiceSpec extends ObjectBehavior
{
    use CommonMatchers;
    
    private Collaborator $roomRepositoryMock;
    private Collaborator $subscriptionsRepositoryMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $blockManagerMock;
    private Collaborator $rolesServiceMock;
    private Collaborator $groupMembershipManagerMock;
    private Collaborator $loggerMock;

    private Collaborator $analyticsDelegateMock;
    private ReflectionClass $chatRoomMockFactory;
    private ReflectionClass $chatRoomListItemMockFactory;

    public function let(
        RoomRepository $roomRepository,
        SubscriptionsRepository $subscriptionsRepository,
        EntitiesBuilder $entitiesBuilder,
        BlockManager $blockManager,
        RolesService $rolesServiceMock,
        GroupMembershipManager $groupMembershipManagerMock,
        AnalyticsDelegate $analyticsDelegate,
        Logger $loggerMock
    ): void {
        $this->roomRepositoryMock = $roomRepository;
        $this->subscriptionsRepositoryMock = $subscriptionsRepository;
        $this->entitiesBuilderMock = $entitiesBuilder;
        $this->blockManagerMock = $blockManager;
        $this->rolesServiceMock = $rolesServiceMock;
        $this->groupMembershipManagerMock = $groupMembershipManagerMock;
        $this->analyticsDelegateMock = $analyticsDelegate;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith(
            $this->roomRepositoryMock,
            $this->subscriptionsRepositoryMock,
            $this->entitiesBuilderMock,
            $this->blockManagerMock,
            $this->rolesServiceMock,
            $this->groupMembershipManagerMock,
            $this->analyticsDelegateMock,
            $this->loggerMock
        );

        $this->chatRoomMockFactory = new ReflectionClass(ChatRoom::class);
        $this->chatRoomListItemMockFactory = new ReflectionClass(ChatRoomListItem::class);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(RoomService::class);
    }

    public function it_should_create_chat_room_NO_ROOM_TYPE_PROVIDED_NO_EXISTING_ROOM_and_SUBSCRIBING_USER(
        User $user,
        User $memberMock
    ): void {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $memberMock->getName()
            ->willReturn('Other user');

        $this->roomRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->roomRepositoryMock->getOneToOneRoomByMembers(
            123,
            456
        )
            ->shouldBeCalledOnce()
            ->willThrow(ChatRoomNotFoundException::class);

        $this->entitiesBuilderMock->single(456)
            ->shouldBeCalledTimes(2)
            ->willReturn($memberMock);

        $this->rolesServiceMock->hasPermission($user, PermissionsEnum::CAN_CREATE_CHAT_ROOM)
            ->shouldBeCalled()
            ->willReturn(true);

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

        $this->roomRepositoryMock->addRoomMemberDefaultSettings(
            Argument::type('integer'),
            123,
            ChatRoomNotificationStatusEnum::ALL
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

        $this->roomRepositoryMock->addRoomMemberDefaultSettings(
            Argument::type('integer'),
            456,
            ChatRoomNotificationStatusEnum::ALL
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->analyticsDelegateMock->onChatRoomCreate(
            actor: $user,
            chatRoom: Argument::type(ChatRoom::class),
        )->shouldBeCalled();
    
        $this->createRoom(
            $user,
            ["456"],
            null
        )
            ->shouldBeAnInstanceOf(ChatRoomEdge::class);
    }

    public function it_should_create_chat_room_NO_ROOM_TYPE_PROVIDED_NO_EXISTING_ROOM_and_NOT_SUBSCRIBING_USER(
        User $user,
        User $memberMock
    ): void {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->entitiesBuilderMock->single(456)
            ->shouldBeCalledTimes(2)
            ->willReturn($memberMock);

        $memberMock->getName()
            ->willReturn('Other user');

        $this->roomRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->roomRepositoryMock->getOneToOneRoomByMembers(
            123,
            456
        )
            ->shouldBeCalledOnce()
            ->willThrow(ChatRoomNotFoundException::class);

        $this->rolesServiceMock->hasPermission($user, PermissionsEnum::CAN_CREATE_CHAT_ROOM)
            ->shouldBeCalled()
            ->willReturn(true);

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

        $this->roomRepositoryMock->addRoomMemberDefaultSettings(
            Argument::type('integer'),
            123,
            ChatRoomNotificationStatusEnum::ALL
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

        $this->roomRepositoryMock->addRoomMemberDefaultSettings(
            Argument::type('integer'),
            456,
            ChatRoomNotificationStatusEnum::ALL
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->analyticsDelegateMock->onChatRoomCreate(
            actor: $user,
            chatRoom: Argument::type(ChatRoom::class),
        )->shouldBeCalled();

        $this->createRoom(
            $user,
            ["456"],
            null
        )
            ->shouldBeAnInstanceOf(ChatRoomEdge::class);
    }

    public function it_should_create_chat_room_WITH_ROOM_TYPE_PROVIDED_NO_EXISTING_ROOM_and_NO_SUBSCRIBING_USER(
        User $user,
        User $memberMock
    ): void {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->entitiesBuilderMock->single(456)
            ->shouldBeCalledTimes(2)
            ->willReturn($memberMock);

        $memberMock->getName()
            ->willReturn('Other user');

        $this->roomRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->roomRepositoryMock->getOneToOneRoomByMembers(
            123,
            456
        )
            ->shouldBeCalledOnce()
            ->willThrow(ChatRoomNotFoundException::class);

        $this->rolesServiceMock->hasPermission($user, PermissionsEnum::CAN_CREATE_CHAT_ROOM)
            ->shouldBeCalled()
            ->willReturn(true);

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

        $this->roomRepositoryMock->addRoomMemberDefaultSettings(
            Argument::type('integer'),
            123,
            ChatRoomNotificationStatusEnum::ALL
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

        $this->roomRepositoryMock->addRoomMemberDefaultSettings(
            Argument::type('integer'),
            456,
            ChatRoomNotificationStatusEnum::ALL
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->analyticsDelegateMock->onChatRoomCreate(
            actor: $user,
            chatRoom: Argument::type(ChatRoom::class),
        )->shouldBeCalled();

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

        $chatRoomMock = new ChatRoom(
            guid: 123,
            roomType: ChatRoomTypeEnum::ONE_TO_ONE,
            createdByGuid: 123,
            name: null,
        );

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

    public function it_should_create_a_group_chat_room(User $userMock, Membership $groupMembershipMock)
    {
        $userGuid = (int) Guid::build();
        $userMock->getGuid()->willReturn($userGuid);
    
        $groupGuid = (int) Guid::build();
        $group = new Group();
        $group->setName('Group name');

        $this->roomRepositoryMock->getGroupRooms($groupGuid)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->entitiesBuilderMock->single($groupGuid)
            ->willReturn($group);

        $this->groupMembershipManagerMock->getMembership($group, $userMock)
            ->willReturn($groupMembershipMock);
        
        $groupMembershipMock->isOwner()
            ->willReturn(true);

        $this->roomRepositoryMock->createRoom(
            Argument::type('integer'),
            ChatRoomTypeEnum::GROUP_OWNED,
            $userGuid,
            Argument::type(DateTimeImmutable::class),
            $groupGuid,
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->createRoom($userMock, [], ChatRoomTypeEnum::GROUP_OWNED, $groupGuid);
    }

    public function it_should_not_create_a_group_chat_room_if_not_owner(User $userMock, Membership $groupMembershipMock)
    {
        $groupGuid = (int) Guid::build();
        $group = new Group();

        $this->roomRepositoryMock->getGroupRooms($groupGuid)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->entitiesBuilderMock->single($groupGuid)
            ->willReturn($group);

        $this->groupMembershipManagerMock->getMembership($group, $userMock)
            ->willReturn($groupMembershipMock);
        
        $groupMembershipMock->isOwner()
            ->willReturn(false);

        $this->shouldThrow(ForbiddenException::class)->duringCreateRoom($userMock, [], ChatRoomTypeEnum::GROUP_OWNED, $groupGuid);
    }

    public function it_should_not_create_a_group_chat_room_if_already_exists(User $userMock, Membership $groupMembershipMock)
    {
        $groupGuid = (int) Guid::build();
        $group = new Group();

        $this->roomRepositoryMock->getGroupRooms($groupGuid)
            ->shouldBeCalled()
            ->willReturn([
                new ChatRoom(123, ChatRoomTypeEnum::GROUP_OWNED, 123)
            ]);

        $this->roomRepositoryMock->createRoom(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->createRoom($userMock, [], ChatRoomTypeEnum::GROUP_OWNED, $groupGuid);
    }

    public function it_should_create_multi_user_chat_room(
        User $userMock,
        User $memberMock1,
        User $memberMock2
    ): void {
        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->entitiesBuilderMock->single(456)
            ->shouldBeCalledTimes(2)
            ->willReturn($memberMock1);

        $memberMock1->getName()
            ->willReturn('Member1');

        $this->entitiesBuilderMock->single(789)
            ->shouldBeCalledTimes(2)
            ->willReturn($memberMock2);

        $memberMock2->getName()
            ->willReturn('Member2');

        $this->roomRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->roomRepositoryMock->getOneToOneRoomByMembers(
            123,
            Argument::type('integer')
        )
            ->shouldNotBeCalled();

        $this->rolesServiceMock->hasPermission($userMock, PermissionsEnum::CAN_CREATE_CHAT_ROOM)
            ->shouldBeCalled()
            ->willReturn(true);

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

        $this->roomRepositoryMock->addRoomMemberDefaultSettings(
            Argument::type('integer'),
            123,
            ChatRoomNotificationStatusEnum::ALL
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

        $this->roomRepositoryMock->addRoomMemberDefaultSettings(
            Argument::type('integer'),
            456,
            ChatRoomNotificationStatusEnum::ALL
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

        $this->roomRepositoryMock->addRoomMemberDefaultSettings(
            Argument::type('integer'),
            789,
            ChatRoomNotificationStatusEnum::ALL
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->analyticsDelegateMock->onChatRoomCreate(
            actor: $userMock,
            chatRoom: Argument::type(ChatRoom::class),
        )->shouldBeCalled();

        $this->createRoom(
            $userMock,
            ["456", "789"],
            ChatRoomTypeEnum::MULTI_USER
        )
            ->shouldBeAnInstanceOf(ChatRoomEdge::class);
    }

    public function it_should_NOT_allow_create_chat_room_when_user_does_not_have_permission(
        User $user
    ): void {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->roomRepositoryMock->getOneToOneRoomByMembers(
            123,
            456
        )
            ->shouldBeCalledOnce()
            ->willThrow(ChatRoomNotFoundException::class);

        $this->rolesServiceMock->hasPermission($user, PermissionsEnum::CAN_CREATE_CHAT_ROOM)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->roomRepositoryMock->createRoom(
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any()
        )
            ->shouldNotBeCalled();

        $this->shouldThrow(GraphQLException::class)->during(
            method: 'createRoom',
            arguments: [
                $user,
                ["456"],
                null
            ]
        );
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
            null,
            null,
            true
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'members' => [
                    [
                        'member_guid' => 456,
                        'joined_timestamp' => date('c'),
                        'role_id' => ChatRoomRoleEnum::OWNER->name,
                        'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value
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
        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn('456');

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

        $this->roomRepositoryMock->isUserRoomOwner(
            123,
            $userMock
        )
            ->shouldBeCalledOnce()
            ->willReturn(false);

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

        $this->roomRepositoryMock->getRoomMemberSettings(
            123,
            456
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value
            ]);

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

    public function it_should_get_room_invite_requests_by_member(
        User $userMock
    ): void {
        $chatRoomListItemMock = $this->generateChatRoomListItem(
            chatRoom: $this->generateChatRoomMock(),
            lastMessagePlainText: null,
            lastMessageCreatedTimestamp: null
        );

        $this->roomRepositoryMock->getRoomsByMember(
            $userMock,
            [ChatRoomMemberStatusEnum::INVITE_PENDING->name],
            12,
            null,
            null,
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'chatRooms' => [
                    $chatRoomListItemMock
                ],
                'hasMore' => false
            ]);

        $response = $this->getRoomInviteRequestsByMember(
            $userMock,
            12,
            null,
        );

        $response->shouldBeArray();
        $response['edges'][0]->shouldBeAnInstanceOf(ChatRoomEdge::class);
        $response['edges'][0]->getNode()->shouldBeAnInstanceOf(ChatRoomNode::class);
        $response['edges'][0]->getNode()->chatRoom->createdAt->shouldEqual($chatRoomListItemMock->chatRoom->createdAt);
    }

    public function it_should_get_total_room_invite_requests_by_member(
        User $userMock
    ): void {
        $this->roomRepositoryMock->getTotalRoomInviteRequestsByMember(
            $userMock
        )
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->getTotalRoomInviteRequestsByMember(
            $userMock
        )
            ->shouldEqual(1);
    }

    public function it_should_ACCEPT_room_invite_request(
        User $userMock
    ): void {
        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn('456');

        $this->roomRepositoryMock->getUserStatusInRoom(
            $userMock,
            123
        )
            ->shouldBeCalledTimes(2)
            ->willReturn(ChatRoomMemberStatusEnum::INVITE_PENDING);

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

        $this->roomRepositoryMock->isUserRoomOwner(
            123,
            $userMock
        )
            ->shouldBeCalledOnce()
            ->willReturn(false);

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
            ->shouldBeCalledOnce()
            ->willReturn([
                'chatRooms' => [
                    $this->generateChatRoomListItem(
                        chatRoom: $this->generateChatRoomMock(),
                        lastMessagePlainText: null,
                        lastMessageCreatedTimestamp: null
                    )
                ],
                'hasMore' => false
            ]);

        $this->roomRepositoryMock->getRoomMemberSettings(
            123,
            456
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value
            ]);

        $this->roomRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        $this->roomRepositoryMock->updateRoomMemberStatus(
            123,
            $userMock,
            ChatRoomMemberStatusEnum::ACTIVE
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->blockManagerMock->add(
            Argument::type(BlockEntry::class)
        )
            ->shouldNotBeCalled();

        $this->roomRepositoryMock->deleteRoom(123)
            ->shouldNotBeCalled();

        $this->roomRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->analyticsDelegateMock->onChatRequestAccept(
            actor: $userMock,
            chatRoom: Argument::type(ChatRoom::class),
        )->shouldBeCalled();

        $this->replyToRoomInviteRequest(
            $userMock,
            123,
            ChatRoomInviteRequestActionEnum::ACCEPT
        )
            ->shouldEqual(true);
    }

    public function it_should_check_if_user_IS_room_member_and_return_TRUE_if_member(
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

        $this->isUserMemberOfRoom(
            $userMock,
            123
        )
            ->shouldEqual(true);
    }

    public function it_should_delete_chat_room(
        User $userMock
    ): void {
        $roomGuid = Guid::build();
        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn('456');

        $this->roomRepositoryMock->isUserMemberOfRoom(
            $roomGuid,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name,
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $chatRoomListItemMock = $this->generateChatRoomListItem(
            chatRoom: $this->generateChatRoomMock(guid: $roomGuid),
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
            null,
            $roomGuid,
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
            $roomGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn(
                ChatRoomMemberStatusEnum::INVITE_PENDING
            );

        $this->roomRepositoryMock->getRoomMemberSettings(
            $roomGuid,
            456
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value
            ]);

        $this->roomRepositoryMock->isUserRoomOwner(
            $roomGuid,
            $userMock
        )
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this->roomRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();
        $this->roomRepositoryMock->deleteRoom($roomGuid)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->roomRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->analyticsDelegateMock->onChatRoomDelete(
            actor: $userMock,
            chatRoom: Argument::type(ChatRoom::class),
        )->shouldBeCalled();

        $this->deleteChatRoomByRoomGuid(
            $roomGuid,
            $userMock
        )
            ->shouldEqual(true);
    }

    public function it_should_throw_exception_when_user_IS_NOT_room_owner_and_try_delete_chat_room(
        User $userMock
    ): void {
        $roomGuid = Guid::build();

        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn('456');

        $this->roomRepositoryMock->isUserMemberOfRoom(
            $roomGuid,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name,
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $chatRoomListItemMock = $this->generateChatRoomListItem(
            chatRoom: $this->generateChatRoomMock(guid: $roomGuid),
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
            null,
            $roomGuid,
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
            $roomGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn(
                ChatRoomMemberStatusEnum::INVITE_PENDING
            );

        $this->roomRepositoryMock->getRoomMemberSettings(
            $roomGuid,
            456
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value
            ]);
   
        $this->roomRepositoryMock->isUserRoomOwner(
            $roomGuid,
            $userMock
        )
            ->shouldBeCalledTimes(2)
            ->willReturn(false);

        $this
            ->shouldThrow(
                new GraphQLException(message: "You are not the owner of this chat.", code: 403)
            )
            ->during(
                method: 'deleteChatRoomByRoomGuid',
                arguments: [
                    $roomGuid,
                    $userMock
                ]
            );
    }

    public function it_should_leave_chat_room(
        User $userMock
    ): void {
        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn('456');

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

        $this->roomRepositoryMock->getRoomMemberSettings(
            123,
            456
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value
            ]);

        $this->roomRepositoryMock->updateRoomMemberStatus(
            123,
            $userMock,
            ChatRoomMemberStatusEnum::LEFT
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->isUserRoomOwner(
            123,
            $userMock
        )
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->analyticsDelegateMock->onChatRoomLeave(
            actor: $userMock,
            chatRoom: Argument::type(ChatRoom::class),
        )->shouldBeCalled();

        $this->leaveChatRoom(
            123,
            $userMock
        )
            ->shouldEqual(true);
    }

    public function it_should_remove_member_from_chat_room(
        User $userMock,
        User $memberMock
    ): void {
        $this->roomRepositoryMock->isUserRoomOwner(
            123,
            $userMock
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->entitiesBuilderMock->single(456)
            ->shouldBeCalledOnce()
            ->willReturn($memberMock);

        $this->roomRepositoryMock->updateRoomMemberStatus(
            123,
            $memberMock,
            ChatRoomMemberStatusEnum::LEFT
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->removeMemberFromChatRoom(
            123,
            456,
            $userMock
        )
            ->shouldEqual(true);
    }

    public function it_should_delete_chat_room_and_block_user(
        User $userMock,
        User $memberMock
    ): void {
        $guid = Guid::build();
        $chatRoomListItemMock = $this->generateChatRoomListItem(
            chatRoom: $this->generateChatRoomMock(guid: $guid),
            lastMessagePlainText: null,
            lastMessageCreatedTimestamp: null
        );

        $this->roomRepositoryMock->getUserStatusInRoom(
            $userMock,
            $guid
        )
            ->shouldBeCalledTimes(2)
            ->willReturn(
                ChatRoomMemberStatusEnum::INVITE_PENDING
            );

        $this->roomRepositoryMock->getRoomMemberSettings(
            $guid,
            456
        )
            ->shouldBeCalledTimes(2)
            ->willReturn([
                'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value
            ]);
            
        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn('456');

        $this->roomRepositoryMock->isUserMemberOfRoom(
            $guid,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ]
        )
            ->shouldBeCalledTimes(3)
            ->willReturn(true);

        $this->roomRepositoryMock->isUserRoomOwner(
            $guid,
            $userMock
        )
            ->shouldBeCalledTimes(3)
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
            $guid
        )
            ->shouldBeCalledTimes(2)
            ->willReturn([
                'chatRooms' => [
                    $chatRoomListItemMock
                ],
                'hasMore' => false
            ]);

        $memberMock->getGuid()
            ->willReturn('456');

        $this->roomRepositoryMock->getRoomMembers(
            $guid,
            $userMock,
            1,
            null,
            null,
            true
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'members' => [
                    [
                        'member_guid' => 456,
                        'joined_timestamp' => date('c'),
                        'role_id' => ChatRoomRoleEnum::OWNER->name,
                        'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value
                    ]
                ],
                'hasMore' => false
            ]);

        $this->entitiesBuilderMock->single(456)
            ->shouldBeCalledOnce()
            ->willReturn(
                $memberMock
            );

        $this->roomRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();
        $this->roomRepositoryMock->deleteRoom($guid)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->roomRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->blockManagerMock->add(
            Argument::type(BlockEntry::class)
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->deleteChatRoomAndBlockUser(
            $guid,
            $userMock
        )
            ->shouldEqual(true);
    }

    public function it_should_throw_exception_when_trying_to_block_user_from_multi_user_room(
        User $userMock
    ): void {
        $userMock->getGuid()
            ->shouldBeCalled()
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

        $this->roomRepositoryMock->isUserRoomOwner(
            123,
            $userMock
        )
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
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'chatRooms' => [
                    $this->generateChatRoomListItem(
                        chatRoom: $this->generateChatRoomMock(
                            roomType: ChatRoomTypeEnum::MULTI_USER
                        ),
                        lastMessagePlainText: null,
                        lastMessageCreatedTimestamp: null
                    )
                ],
                'hasMore' => false
            ]);

        $this->roomRepositoryMock->getRoomMemberSettings(
            123,
            456
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value
            ]);

        $this->roomRepositoryMock->getUserStatusInRoom(
            $userMock,
            123
        )
            ->shouldBeCalledOnce()
            ->willReturn(
                ChatRoomMemberStatusEnum::INVITE_PENDING
            );

        $this
            ->shouldThrow(
                new GraphQLException(message: "You can only block users in one-to-one rooms", code: 400)
            )
            ->during(
                method: 'deleteChatRoomAndBlockUser',
                arguments: [
                    123,
                    $userMock
                ]
            );
    }

    public function it_should_get_rooms_by_group(): void
    {
        $groupGuid = Guid::build();

        $chatRooms = [
            array_fill(
                0,
                6,
                $this->generateChatRoomListItem(
                    chatRoom: $this->generateChatRoomMock()
                )
            )
        ];

        $this->roomRepositoryMock->getGroupRooms($groupGuid)
            ->shouldBeCalled()
            ->willReturn($chatRooms);

        $this->getRoomsByGroup($groupGuid)
            ->shouldBe($chatRooms);
    }

    public function it_should_get_rooms_by_group_returning_empty_array_when_none_are_found(): void
    {
        $groupGuid = Guid::build();

        $chatRooms = [];

        $this->roomRepositoryMock->getGroupRooms($groupGuid)
            ->shouldBeCalled()
            ->willReturn($chatRooms);

        $this->getRoomsByGroup($groupGuid)
            ->shouldBe($chatRooms);
    }

    // updateRoomName

    public function it_should_update_room_name(User $userMock): void
    {
        $roomGuid = Guid::build();
        $userGuid = Guid::build();
        $roomName = 'Room name';

        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

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
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'chatRooms' => [
                    $this->generateChatRoomListItem(
                        chatRoom: $this->generateChatRoomMock(roomType: ChatRoomTypeEnum::MULTI_USER),
                        lastMessagePlainText: null,
                        lastMessageCreatedTimestamp: null
                    )
                ],
                'hasMore' => false
            ]);

        $this->roomRepositoryMock->getRoomMemberSettings(
            $roomGuid,
            $userGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value
            ]);

        $this->roomRepositoryMock->isUserMemberOfRoom(
            $roomGuid,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->getUserStatusInRoom(
            $userMock,
            $roomGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn(
                ChatRoomMemberStatusEnum::INVITE_PENDING
            );

        $this->roomRepositoryMock->isUserRoomOwner(
            roomGuid: $roomGuid,
            user: $userMock
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->roomRepositoryMock->updateRoomName($roomGuid, $roomName)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->updateRoomName($roomGuid, $roomName, $userMock)
            ->shouldEqual(true);
    }

    public function it_should_not_update_room_name_when_not_the_owner(User $userMock): void
    {
        $roomGuid = Guid::build();
        $userGuid = Guid::build();
        $roomName = 'Room name';

        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

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
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'chatRooms' => [
                    $this->generateChatRoomListItem(
                        chatRoom: $this->generateChatRoomMock(roomType: ChatRoomTypeEnum::MULTI_USER),
                        lastMessagePlainText: null,
                        lastMessageCreatedTimestamp: null
                    )
                ],
                'hasMore' => false
            ]);

        $this->roomRepositoryMock->getRoomMemberSettings(
            $roomGuid,
            $userGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value
            ]);

        $this->roomRepositoryMock->isUserMemberOfRoom(
            $roomGuid,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->getUserStatusInRoom(
            $userMock,
            $roomGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn(
                ChatRoomMemberStatusEnum::INVITE_PENDING
            );

        $this->roomRepositoryMock->isUserRoomOwner(
            roomGuid: $roomGuid,
            user: $userMock
        )
            ->shouldBeCalled()
            ->willReturn(false);

        $this->roomRepositoryMock->updateRoomName($roomGuid, $roomName)
            ->shouldNotBeCalled();

        $this->shouldThrow(new GraphQLException(message: "You are not the owner of this chat.", code: 403))->duringUpdateRoomName($roomGuid, $roomName, $userMock);
    }

    public function it_should_not_update_room_name_when_room_name_too_long(User $userMock): void
    {
        $roomGuid = Guid::build();
        $userGuid = Guid::build();
        $roomName = str_repeat('a', 129);

        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

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
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'chatRooms' => [
                    $this->generateChatRoomListItem(
                        chatRoom: $this->generateChatRoomMock(roomType: ChatRoomTypeEnum::MULTI_USER),
                        lastMessagePlainText: null,
                        lastMessageCreatedTimestamp: null
                    )
                ],
                'hasMore' => false
            ]);

        $this->roomRepositoryMock->getRoomMemberSettings(
            $roomGuid,
            $userGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value
            ]);

        $this->roomRepositoryMock->isUserMemberOfRoom(
            $roomGuid,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->getUserStatusInRoom(
            $userMock,
            $roomGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn(
                ChatRoomMemberStatusEnum::INVITE_PENDING
            );

        $this->roomRepositoryMock->isUserRoomOwner(
            roomGuid: $roomGuid,
            user: $userMock
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->roomRepositoryMock->updateRoomName($roomGuid, $roomName)
            ->shouldNotBeCalled();

        $this->shouldThrow(new UserErrorException("Room name must be under 128 characters", code: 400))->duringUpdateRoomName($roomGuid, $roomName, $userMock);
    }

    // addRoomMembers

    public function it_should_add_room_members(
        User $userMock,
        User $memberMock1,
        User $memberMock2
    ): void {
        $roomGuid = (int) Guid::build();
        $memberGuids = [Guid::build(), Guid::build()];
        $userGuid = (int) Guid::build();
        $roomType = ChatRoomTypeEnum::MULTI_USER;
        $isUserRoomMember = true;

        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        // get room

        $chatRoomListItemMock = $this->generateChatRoomListItem(
            chatRoom: $this->generateChatRoomMock(
                roomType: $roomType
            ),
            lastMessagePlainText: null,
            lastMessageCreatedTimestamp: null
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
            $roomGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'chatRooms' => [
                    $chatRoomListItemMock
                ],
                'hasMore' => false
            ]);

        $this->roomRepositoryMock->isUserMemberOfRoom(
            $roomGuid,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn($isUserRoomMember);

        $this->roomRepositoryMock->getRoomMemberSettings(
            $roomGuid,
            $userGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value
            ]);

        $this->roomRepositoryMock->getUserStatusInRoom(
            $userMock,
            $roomGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn(ChatRoomMemberStatusEnum::ACTIVE);

        $this->roomRepositoryMock->isUserRoomOwner(
            $roomGuid,
            $userMock
        )
            ->shouldBeCalledOnce()
            ->willReturn(false);

        //

        $this->roomRepositoryMock->beginTransaction()
            ->shouldBeCalledOnce();

        // loop member 1

        $this->entitiesBuilderMock->single($memberGuids[0])
            ->shouldBeCalled()
            ->willReturn($memberMock1);

        $this->roomRepositoryMock->isUserMemberOfRoom(
            $roomGuid,
            $memberMock1,
            null
        )
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->subscriptionsRepositoryMock->isSubscribed(
            $memberGuids[0],
            $userGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->roomRepositoryMock->addRoomMember(
            roomGuid: Argument::any(),
            memberGuid: (int)$memberGuids[0],
            status: Argument::any(),
            role: ChatRoomRoleEnum::MEMBER
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->updateRoomMemberSettings(
            roomGuid: Argument::any(),
            memberGuid: (int)$memberGuids[0],
            notificationStatus: ChatRoomNotificationStatusEnum::ALL
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        // loop member 2

        $this->entitiesBuilderMock->single($memberGuids[1])
            ->shouldBeCalled()
            ->willReturn($memberMock2);

        $this->roomRepositoryMock->isUserMemberOfRoom(
            $roomGuid,
            $memberMock2,
            null
        )
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->subscriptionsRepositoryMock->isSubscribed(
            $memberGuids[1],
            $userGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->roomRepositoryMock->addRoomMember(
            roomGuid: Argument::any(),
            memberGuid: (int)$memberGuids[1],
            status: Argument::any(),
            role: ChatRoomRoleEnum::MEMBER
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->roomRepositoryMock->updateRoomMemberSettings(
            roomGuid: Argument::any(),
            memberGuid: (int)$memberGuids[1],
            notificationStatus: ChatRoomNotificationStatusEnum::ALL
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        //

        $this->roomRepositoryMock->commitTransaction()
            ->shouldBeCalledOnce();

        $this->addRoomMembers(
            $roomGuid,
            $memberGuids,
            $userMock
        )
            ->shouldEqual(true);
    }

    public function it_should_not_add_room_members_when_a_room_is_not_multi_user(
        User $userMock
    ): void {
        $roomGuid = (int) Guid::build();
        $memberGuids = [Guid::build(), Guid::build()];
        $userGuid = (int) Guid::build();
        $roomType = ChatRoomTypeEnum::ONE_TO_ONE;
        $isUserRoomMember = true;

        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        // get room

        $chatRoomListItemMock = $this->generateChatRoomListItem(
            chatRoom: $this->generateChatRoomMock(
                roomType: $roomType
            ),
            lastMessagePlainText: null,
            lastMessageCreatedTimestamp: null
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
            $roomGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'chatRooms' => [
                    $chatRoomListItemMock
                ],
                'hasMore' => false
            ]);

        $this->roomRepositoryMock->isUserMemberOfRoom(
            $roomGuid,
            $userMock,
            [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ]
        )
            ->shouldBeCalledOnce()
            ->willReturn($isUserRoomMember);

        $this->roomRepositoryMock->getRoomMemberSettings(
            $roomGuid,
            $userGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'notifications_status' => ChatRoomNotificationStatusEnum::ALL->value
            ]);

        $this->roomRepositoryMock->getUserStatusInRoom(
            $userMock,
            $roomGuid
        )
            ->shouldBeCalledOnce()
            ->willReturn(ChatRoomMemberStatusEnum::ACTIVE);

        $this->roomRepositoryMock->isUserRoomOwner(
            $roomGuid,
            $userMock
        )
            ->shouldBeCalledOnce()
            ->willReturn(false);

        //

        $this->shouldThrow(new GraphQLException(message: "You can only add members to multi-user rooms", code: 400))->duringAddRoomMembers(
            $roomGuid,
            $memberGuids,
            $userMock
        );
    }

    private function generateChatRoomMock(
        ChatRoomTypeEnum $roomType = ChatRoomTypeEnum::ONE_TO_ONE,
        int $guid = null
    ): ChatRoom {
        $chatRoom = $this->chatRoomMockFactory->newInstanceWithoutConstructor();
        $this->chatRoomMockFactory->getProperty('guid')->setValue($chatRoom, $guid ?? Guid::build());
        $this->chatRoomMockFactory->getProperty('createdAt')->setValue($chatRoom, new DateTimeImmutable());
        $this->chatRoomMockFactory->getProperty('roomType')->setValue($chatRoom, $roomType);
        $this->chatRoomMockFactory->getProperty('name')->setValue($chatRoom, null);

        return $chatRoom;
    }

    private function generateChatRoomListItem(
        ChatRoom $chatRoom,
        string|null $lastMessagePlainText = null,
        int|null $lastMessageCreatedTimestamp = null,
        array $memberGuids = [],
    ): ChatRoomListItem {
        $chatRoomListItem = $this->chatRoomListItemMockFactory->newInstanceWithoutConstructor();
        $this->chatRoomListItemMockFactory->getProperty('chatRoom')->setValue($chatRoomListItem, $chatRoom);
        $this->chatRoomListItemMockFactory->getProperty('lastMessagePlainText')->setValue($chatRoomListItem, $lastMessagePlainText);
        $this->chatRoomListItemMockFactory->getProperty('lastMessageCreatedTimestamp')->setValue($chatRoomListItem, $lastMessageCreatedTimestamp);
        $this->chatRoomListItemMockFactory->getProperty('unreadMessagesCount')->setValue($chatRoomListItem, 0);
        $this->chatRoomListItemMockFactory->getProperty('memberGuids')->setValue($chatRoomListItem, $memberGuids);

        return $chatRoomListItem;
    }
}
