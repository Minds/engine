<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use DateTimeImmutable;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Types\ChatRoomNode;
use Minds\Core\Guid;
use Minds\Core\Subscriptions\Relational\Repository as SubscriptionsRepository;
use Minds\Entities\User;

class RoomService
{
    public function __construct(
        private readonly RoomRepository          $roomRepository,
        private readonly SubscriptionsRepository $subscriptionsRepository
    ) {
    }

    /**
     * @param User $user
     * @param array $members
     * @return ChatRoomNode
     */
    public function createRoom(User $user, array $members): ChatRoomNode
    {
        $roomGuid = Guid::build();

        $chatRoom = new ChatRoom(
            guid: $roomGuid,
            roomType: count($members > 1) ? ChatRoomTypeEnum::MULTI_USER : ChatRoomTypeEnum::ONE_TO_ONE,
            createdByGuid: $user->getGuid(),
            createdAt: new DateTimeImmutable(),
        );

        $this->roomRepository->createRoom(
            roomGuid: $chatRoom->guid,
            roomType: $chatRoom->roomType,
            createdByGuid: $chatRoom->createdByGuid,
            createdAt: $chatRoom->createdAt,
        );

        return new ChatRoomNode(chatRoom: $chatRoom);
    }

    public function createGroupOwnedRoom(User $user, int $groupGuid): ChatRoomNode
    {
        $roomGuid = Guid::build();

        $chatRoom = new ChatRoom(
            guid: $roomGuid,
            roomType: ChatRoomTypeEnum::GROUP_OWNED,
            createdByGuid: $user->getGuid(),
            createdAt: new DateTimeImmutable(),
        );

        $this->roomRepository->createRoom(
            roomGuid: $chatRoom->guid,
            roomType: $chatRoom->roomType,
            createdByGuid: $chatRoom->createdByGuid,
            createdAt: $chatRoom->createdAt,
            groupGuid: $groupGuid,
        );

        return new ChatRoomNode(chatRoom: $chatRoom);
    }
}
