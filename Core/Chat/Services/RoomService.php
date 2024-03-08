<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use DateTimeImmutable;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomRoleEnum;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Exceptions\InvalidChatRoomTypeException;
use Minds\Core\Chat\Repositories\RoomRepository;
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
        array $otherMembers,
        ?ChatRoomTypeEnum $roomType = null
    ): ChatRoomNode {
        if ($roomType === ChatRoomTypeEnum::GROUP_OWNED) {
            throw new InvalidChatRoomTypeException();
        }

        if (!$roomType) {
            $roomType = count($otherMembers) > 1 ? ChatRoomTypeEnum::MULTI_USER : ChatRoomTypeEnum::ONE_TO_ONE;
        }

        $roomGuid = Guid::build();

        $chatRoom = new ChatRoom(
            guid: $roomGuid,
            roomType: $roomType,
            createdByGuid: $user->getGuid(),
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

            $totalMembers = count($otherMembers);

            foreach ($otherMembers as $memberGuid) {
                $isSubscribed = $this->subscriptionsRepository->isSubscribed(
                    userGuid: $memberGuid,
                    friendGuid: (int) $user->getGuid()
                );

                $this->roomRepository->addRoomMember(
                    roomGuid: $roomGuid,
                    memberGuid: $memberGuid,
                    status: $isSubscribed ? ChatRoomMemberStatusEnum::ACTIVE : ChatRoomMemberStatusEnum::INVITE_PENDING,
                    role: $totalMembers === 1 ? ChatRoomRoleEnum::OWNER : ChatRoomRoleEnum::MEMBER,
                );

                // TODO: Add push notifications and emails
            }
        } catch (ServerErrorException $e) {
            $this->roomRepository->rollbackTransaction();
            throw $e;
        }

        return new ChatRoomNode(chatRoom: $chatRoom);
    }

    /**
     * @param User $user
     * @param int $groupGuid
     * @return ChatRoomNode
     * @throws ServerErrorException
     */
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

    /**
     * @param User $user
     * @return array<ChatRoomNode>
     * @throws ServerErrorException
     */
    public function getRoomsByMember(
        User $user
    ): array {
        $chatRooms = $this->roomRepository->getRoomsByMember($user);

        $chatRoomNodes = [];

        foreach ($chatRooms as $chatRoom) {
            $chatRoomNodes[] = new ChatRoomNode(chatRoom: $chatRoom);
        }

        return $chatRoomNodes;
    }

    public function getRoomTotalMembers(
        int $roomGuid
    ): int {
        return $this->roomRepository->getRoomTotalMembers($roomGuid);
    }
}
