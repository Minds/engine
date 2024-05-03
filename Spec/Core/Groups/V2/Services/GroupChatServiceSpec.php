<?php

namespace Spec\Minds\Core\Groups\V2\Services;

use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Services\RoomService as ChatRoomService;
use Minds\Core\Chat\Types\ChatRoomEdge;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Services\GroupChatService;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use ReflectionClass;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class GroupChatServiceSpec extends ObjectBehavior
{
    private Collaborator $chatRoomServiceMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $saveActionMock;
    private Collaborator $loggerMock;

    private ReflectionClass $chatRoomMockFactory;

    public function let(
        ChatRoomService $chatRoomService,
        EntitiesBuilder $entitiesBuilder,
        SaveAction $saveAction,
        Logger $logger
    ): void {
        $this->beConstructedWith(
            $chatRoomService,
            $entitiesBuilder,
            $saveAction,
            $logger
        );
        $this->chatRoomServiceMock = $chatRoomService;
        $this->entitiesBuilderMock = $entitiesBuilder;
        $this->saveActionMock = $saveAction;
        $this->loggerMock = $logger;

        $this->chatRoomMockFactory = new ReflectionClass(ChatRoom::class);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(GroupChatService::class);
    }

    // createGroupChatRoom

    public function it_should_create_a_group_chat_room(
        Group $group,
        User $loggedInUser,
        ChatRoomEdge $chatRoomEdge
    ): void {
        $groupGuid = Guid::build();

        $this->entitiesBuilderMock->single($groupGuid)
            ->shouldBeCalled()
            ->willReturn($group);

        $this->chatRoomServiceMock->createRoom(
            user: $loggedInUser,
            otherMemberGuids: [],
            roomType: ChatRoomTypeEnum::GROUP_OWNED,
            groupGuid: $groupGuid
        )
            ->shouldBeCalled()
            ->willReturn($chatRoomEdge);

        $group->setConversationDisabled(false)
            ->shouldBeCalled();

        $this->saveActionMock->setEntity($group)
            ->shouldBeCalled()
            ->willReturn($this->saveActionMock);

        $this->saveActionMock->withMutatedAttributes(['conversation_disabled', 'conversationDisabled'])
            ->shouldBeCalled()
            ->willReturn($this->saveActionMock);

        $this->saveActionMock->save()
            ->shouldBeCalled();

        $this->createGroupChatRoom($groupGuid, $loggedInUser)
            ->shouldReturn($chatRoomEdge);
    }

    public function it_should_throw_an_error_when_creating_a_group_chat_room_when_the_group_does_not_exist(
        User $loggedInUser
    ): void {
        $groupGuid = Guid::build();

        $this->entitiesBuilderMock->single($groupGuid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->shouldThrow(new GraphQLException(message: "A valid Group could not be found.", code: 404))
            ->during('createGroupChatRoom', [$groupGuid, $loggedInUser]);
    }

    public function it_should_throw_an_error_when_creating_a_group_chat_room_when_the_group_guid_is_not_for_a_group(
        Activity $activity,
        User $loggedInUser
    ): void {
        $groupGuid = Guid::build();

        $this->entitiesBuilderMock->single($groupGuid)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->shouldThrow(new GraphQLException(message: "A valid Group could not be found.", code: 404))
            ->during('createGroupChatRoom', [$groupGuid, $loggedInUser]);
    }

    // delete group chat rooms

    public function it_should_delete_group_chat_rooms(
        Group $group,
        User $loggedInUser,
    ): void {
        $chatRoom1 = $this->generateChatRoomMock(roomType: ChatRoomTypeEnum::GROUP_OWNED);
        $chatRoom2 = $this->generateChatRoomMock(roomType: ChatRoomTypeEnum::GROUP_OWNED);

        $groupGuid = Guid::build();

        $this->entitiesBuilderMock->single($groupGuid)
            ->shouldBeCalled()
            ->willReturn($group);

        $this->chatRoomServiceMock->getRoomsByGroup($groupGuid)
            ->shouldBeCalled()
            ->willReturn([$chatRoom1, $chatRoom2]);

        $this->chatRoomServiceMock->deleteChatRoom($chatRoom1, $loggedInUser)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->chatRoomServiceMock->deleteChatRoom($chatRoom2, $loggedInUser)
            ->shouldNotBeCalled();

        $group->setConversationDisabled(true)
            ->shouldBeCalled();

        $this->saveActionMock->setEntity($group)
            ->shouldBeCalled()
            ->willReturn($this->saveActionMock);

        $this->saveActionMock->withMutatedAttributes(['conversation_disabled', 'conversationDisabled'])
            ->shouldBeCalled()
            ->willReturn($this->saveActionMock);

        $this->saveActionMock->save()
            ->shouldBeCalled();

        $this->deleteGroupChatRooms($groupGuid, $loggedInUser)
            ->shouldReturn(true);
    }

    public function it_should_throw_an_error_when_deleting_group_chat_rooms_when_the_group_does_not_exist(
        User $loggedInUser,
    ): void {
        $groupGuid = Guid::build();

        $this->entitiesBuilderMock->single($groupGuid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->chatRoomServiceMock->deleteChatRoom(Argument::any(), $loggedInUser)
            ->shouldNotBeCalled();

        $this->saveActionMock->save()
            ->shouldNotBeCalled();

        $this->shouldThrow(new GraphQLException(message: "A valid Group could not be found.", code: 404))
            ->duringDeleteGroupChatRooms($groupGuid, $loggedInUser);
    }

    public function it_should_throw_an_error_when_deleting_group_chat_rooms_when_the_group_has_no_chat_rooms(
        Group $group,
        User $loggedInUser,
    ): void {
        $groupGuid = Guid::build();

        $this->entitiesBuilderMock->single($groupGuid)
            ->shouldBeCalled()
            ->willReturn($group);

        $this->chatRoomServiceMock->getRoomsByGroup($groupGuid)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->chatRoomServiceMock->deleteChatRoom(Argument::any(), $loggedInUser)
            ->shouldNotBeCalled()
            ->willReturn(true);

        $this->saveActionMock->save()
            ->shouldNotBeCalled();

        $this->shouldThrow(new GraphQLException(message: "No group chat rooms found.", code: 404))
            ->duringDeleteGroupChatRooms($groupGuid, $loggedInUser);
    }

    // mock builders

    private function generateChatRoomMock(
        int $guid = null,
        ChatRoomTypeEnum $roomType = ChatRoomTypeEnum::ONE_TO_ONE,
    ): ChatRoom {
        $chatRoom = $this->chatRoomMockFactory->newInstanceWithoutConstructor();

        $this->chatRoomMockFactory->getProperty('guid')->setValue($chatRoom, $guid ?? Guid::build());
        $this->chatRoomMockFactory->getProperty('roomType')->setValue($chatRoom, $roomType);
        
        return $chatRoom;
    }
}
