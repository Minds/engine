<?php
declare(strict_types=1);

namespace Minds\Core\Groups\V2\GraphQL\Controllers;

use Minds\Core\Chat\Types\ChatRoomEdge;
use Minds\Core\Groups\V2\Services\GroupChatService;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

/**
 * Controller for group chats.
 */
class GroupChatController
{
    public function __construct(
        protected readonly GroupChatService $groupChatService
    ) {
    }

    /**
     * Creates a new group chat room.
     * @param string $groupGuid
     * @return ChatRoomEdge
     * @throws GraphQLException
     * @throws InvalidChatRoomTypeException
     * @throws ServerErrorException
     */
    #[Mutation]
    #[Logged]
    public function createGroupChatRoom(
        string $groupGuid,
        #[InjectUser] User $loggedInUser,
    ): ChatRoomEdge {
        return $this->groupChatService->createGroupChatRoom(
            groupGuid: (int) $groupGuid,
            user: $loggedInUser,
        );
    }

    /**
     * Deletes group chat rooms.
     * @param string $groupGuid
     * @return bool
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    #[Mutation]
    #[Logged]
    public function deleteGroupChatRooms(
        string $groupGuid,
        #[InjectUser] User $loggedInUser
    ): bool {
        return $this->groupChatService->deleteGroupChatRooms(
            groupGuid: (int) $groupGuid,
            user: $loggedInUser
        );
    }
}
