<?php

namespace Spec\Minds\Core\Groups\V2\GraphQL\Controllers;

use Minds\Core\Chat\Types\ChatRoomEdge;
use Minds\Core\Groups\V2\GraphQL\Controllers\GroupChatController;
use Minds\Core\Groups\V2\Services\GroupChatService;
use Minds\Core\Guid;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class GroupChatControllerSpec extends ObjectBehavior
{
    private Collaborator $groupChatServiceMock;

    public function let(
        GroupChatService $groupChatService
    ): void {
        $this->beConstructedWith($groupChatService);
        $this->groupChatServiceMock = $groupChatService;
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(GroupChatController::class);
    }

    // createGroupChatRoom

    public function it_should_create_group_chat_rooms(User $loggedInUser, ChatRoomEdge $chatRoomEdge): void
    {
        $groupGuid = Guid::build();

        $this->groupChatServiceMock->createGroupChatRoom(
            groupGuid: $groupGuid,
            user: $loggedInUser
        )->willReturn($chatRoomEdge);

        $this->createGroupChatRoom($groupGuid, $loggedInUser)->shouldReturn($chatRoomEdge);
    }

    // deleteGroupChatRooms

    public function it_should_delete_group_chat_rooms(User $loggedInUser): void
    {
        $groupGuid = Guid::build();

        $this->groupChatServiceMock->deleteGroupChatRooms(
            groupGuid: $groupGuid,
            user: $loggedInUser
        )->willReturn(true);

        $this->deleteGroupChatRooms($groupGuid, $loggedInUser)->shouldReturn(true);
    }
}
