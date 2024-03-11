<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use DateTimeImmutable;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Entities\ChatRoomListItem;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomRoleEnum;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Exceptions\InvalidChatRoomTypeException;
use Minds\Core\Chat\Repositories\RoomRepository;
use Minds\Core\Chat\Types\ChatRoomEdge;
use Minds\Core\Chat\Types\ChatRoomNode;
use Minds\Core\Guid;
use Minds\Core\Subscriptions\Relational\Repository as SubscriptionsRepository;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

class RoomService
{
    public function __construct(
        private readonly RoomRepository          $roomRepository,
        private readonly SubscriptionsRepository $subscriptionsRepository
    ) {
    }

    /**
     * @param User $user
     * @param array $otherMembers
     * @param ChatRoomTypeEnum|null $roomType
     * @return ChatRoomNode
     * @throws InvalidChatRoomTypeException
     * @throws ServerErrorException
     */
    public function createRoom(
        User $user,
        array $otherMemberGuids,
        ?ChatRoomTypeEnum $roomType = null
    ): ChatRoomEdge {
        if ($roomType === ChatRoomTypeEnum::GROUP_OWNED) {
            throw new InvalidChatRoomTypeException();
        }

        if (!$roomType) {
            $roomType = count($otherMemberGuids) > 1 ? ChatRoomTypeEnum::MULTI_USER : ChatRoomTypeEnum::ONE_TO_ONE;
        }

        $roomGuid = Guid::build();

        $chatRoom = new ChatRoom(
            guid: (int) $roomGuid,
            roomType: $roomType,
            createdByGuid: (int) $user->getGuid(),
            createdAt: new DateTimeImmutable(),
        );

        $this->roomRepository->beginTransaction();

        try {
            $this->roomRepository->createRoom(
                roomGuid: $chatRoom->guid,
                roomType: $chatRoom->roomType,
                createdByGuid: $chatRoom->createdByGuid,
                createdAt: $chatRoom->createdAt,
            );

            $this->roomRepository->addRoomMember(
                roomGuid: $roomGuid,
                memberGuid: (int) $user->getGuid(),
                status: ChatRoomMemberStatusEnum::ACTIVE,
                role: ChatRoomRoleEnum::OWNER,
            );

            foreach ($otherMemberGuids as $memberGuid) {
                $isSubscribed = $this->subscriptionsRepository->isSubscribed(
                    userGuid: $memberGuid,
                    friendGuid: (int) $user->getGuid()
                );

                $this->roomRepository->addRoomMember(
                    roomGuid: $roomGuid,
                    memberGuid: $memberGuid,
                    status: $isSubscribed ? ChatRoomMemberStatusEnum::ACTIVE : ChatRoomMemberStatusEnum::INVITE_PENDING,
                    role: $roomType === ChatRoomTypeEnum::ONE_TO_ONE ? ChatRoomRoleEnum::OWNER : ChatRoomRoleEnum::MEMBER,
                );

                // TODO: Add push notifications and emails
            }
        } catch (ServerErrorException $e) {
            $this->roomRepository->rollbackTransaction();
            throw $e;
        }

        $this->roomRepository->commitTransaction();

        return new ChatRoomEdge(
            node: new ChatRoomNode(chatRoom: $chatRoom)
        );
    }

    /**
     * @param User $user
     * @param int $groupGuid
     * @return ChatRoomNode
     * @throws ServerErrorException
     */
    public function createGroupOwnedRoom(User $user, int $groupGuid): ChatRoomEdge
    {
        $roomGuid = Guid::build();

        $chatRoom = new ChatRoom(
            guid: (int) $roomGuid,
            roomType: ChatRoomTypeEnum::GROUP_OWNED,
            createdByGuid: (int) $user->getGuid(),
            createdAt: new DateTimeImmutable(),
        );

        $this->roomRepository->createRoom(
            roomGuid: $chatRoom->guid,
            roomType: $chatRoom->roomType,
            createdByGuid: $chatRoom->createdByGuid,
            createdAt: $chatRoom->createdAt,
            groupGuid: $groupGuid,
        );

        return new ChatRoomEdge(
            node: new ChatRoomNode(chatRoom: $chatRoom)
        );
    }

    /**
     * @param User $user
     * @return array<ChatRoomEdge>
     * @throws ServerErrorException
     */
    public function getRoomsByMember(
        User $user
    ): array {
        $chatRooms = $this->roomRepository->getRoomsByMember($user);

        return array_map(
            fn (ChatRoomListItem $chatRoomListItem) => new ChatRoomEdge(
                node: new ChatRoomNode(
                    chatRoom: $chatRoomListItem->chatRoom
                ),
                lastMessagePlainText: $chatRoomListItem->lastMessagePlainText,
                lastMessageCreatedTimestamp: $chatRoomListItem->lastMessageCreatedTimestamp,
            ),
            iterator_to_array($chatRooms)
        );
    }

    public function getRoomTotalMembers(
        int $roomGuid
    ): int {
        return $this->roomRepository->getRoomTotalMembers($roomGuid);
    }
}
