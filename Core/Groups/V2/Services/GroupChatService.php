<?php
declare(strict_types=1);

namespace Minds\Core\Groups\V2\Services;

use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Services\RoomService as ChatRoomService;
use Minds\Core\Chat\Types\ChatRoomEdge;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Entities\Group;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

/**
 * Group chat service.
 */
class GroupChatService
{
    public function __construct(
        protected readonly ChatRoomService $chatRoomService,
        protected readonly EntitiesBuilder $entitiesBuilder,
        protected readonly SaveAction $saveAction,
        protected readonly Logger $logger
    ) {
    }

    /**
     * Create group chat room.
     * @param int $groupGuid - Group guid.
     * @param User $user - User entity.
     * @throws GraphQLException - on error.
     * @return ChatRoomEdge - Chat room edge.
     */
    public function createGroupChatRoom(
        int $groupGuid,
        User $user
    ): ChatRoomEdge {
        $group = $this->entitiesBuilder->single($groupGuid);

        if (!$group || !($group instanceof Group)) {
            throw new GraphQLException(message: "A valid Group could not be found.", code: 404);
        }

        $chatRoom = $this->chatRoomService->createRoom(
            user: $user,
            otherMemberGuids: [],
            roomType: ChatRoomTypeEnum::GROUP_OWNED,
            groupGuid: $groupGuid
        );

        if ($group->getOwnerGuid() === $user->getGuid() && $group->isConversationDisabled()) {
            $this->updateConversationDisabledState($group, false);
        }

        return $chatRoom;
    }

    /**
     * Delete all chat rooms for a group.
     * @param int $groupGuid - Group guid.
     * @param User $user - User entity.
     * @throws GraphQLException - on error.
     * @return bool - True on success.
     */
    public function deleteGroupChatRooms(
        int $groupGuid,
        User $user
    ): bool {
        $group = $this->entitiesBuilder->single($groupGuid);

        if (!$group || !($group instanceof Group)) {
            throw new GraphQLException(message: "A valid Group could not be found.", code: 404);
        }

        $chatRooms = $this->chatRoomService->getRoomsByGroup($groupGuid);

        if (!count($chatRooms)) {
            throw new GraphQLException(message: "No group chat rooms found.", code: 404);
        }

        $success = false;

        foreach ($chatRooms as $chatRoom) {
            if ($chatRoom->roomType !== ChatRoomTypeEnum::GROUP_OWNED) {
                $this->logger->error("Tried to delete chat room with guid: $chatRoom->guid, which is not a group owned room.");
                break;
            }

            if ($success = $this->chatRoomService->deleteChatRoom($chatRoom, $user)) {
                $this->logger->error("Tried to delete chat room with guid: $chatRoom->guid");
                break;
            }
        }

        if ($success) {
            $this->updateConversationDisabledState($group, true);
        }

        return $success;
    }

    /**
     * Update a Group's conversation disabled state.
     * @param Group $group - Group entity.
     * @param boolean $state - State to set.
     * @return void
     */
    private function updateConversationDisabledState(Group $group, bool $state): void
    {
        $group->setConversationDisabled($state);

        /**
         * conversationDisabled is the Cassandra version of conversation_disabled.
         * See the Group entities toArray() function.
         */
        $this->saveAction->setEntity($group)->withMutatedAttributes(['conversation_disabled', 'conversationDisabled'])->save();
    }
}
