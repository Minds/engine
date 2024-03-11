<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use DateTimeImmutable;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Entities\ChatRoomListItem;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomRoleEnum;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Exceptions\ChatRoomNotFoundException;
use Minds\Core\Chat\Exceptions\InvalidChatRoomTypeException;
use Minds\Core\Chat\Repositories\RoomRepository;
use Minds\Core\Chat\Types\ChatRoomEdge;
use Minds\Core\Chat\Types\ChatRoomMemberEdge;
use Minds\Core\Chat\Types\ChatRoomNode;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\GraphQL\Types\UserNode;
use Minds\Core\Guid;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Subscriptions\Relational\Repository as SubscriptionsRepository;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

class RoomService
{
    public function __construct(
        private readonly RoomRepository          $roomRepository,
        private readonly SubscriptionsRepository $subscriptionsRepository,
        private readonly EntitiesBuilder         $entitiesBuilder
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

        $chatRoom = new ChatRoom(
            guid: (int) Guid::build(),
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
                roomGuid: $chatRoom->guid,
                memberGuid: (int) $user->getGuid(),
                status: ChatRoomMemberStatusEnum::ACTIVE,
                role: ChatRoomRoleEnum::OWNER,
            );

            foreach ($otherMemberGuids as $memberGuid) {
                $isSubscribed = $this->subscriptionsRepository->isSubscribed(
                    userGuid: (int)$memberGuid,
                    friendGuid: (int) $user->getGuid()
                );

                $this->roomRepository->addRoomMember(
                    roomGuid: $chatRoom->guid,
                    memberGuid: (int) $memberGuid,
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
        $chatRoom = new ChatRoom(
            guid: (int) Guid::build(),
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

    /**
     * @param int $roomGuid
     * @param User $loggedInUser
     * @param int|null $first
     * @return array
     * @throws ForbiddenException
     * @throws ServerErrorException
     */
    public function getRoomMembers(
        int $roomGuid,
        User $loggedInUser,
        ?int $first = null
    ): array {
        if (
            !$this->roomRepository->isUserMemberOfRoom(
                roomGuid: $roomGuid,
                user: $loggedInUser
            )
        ) {
            throw new ForbiddenException("You are not a member of this chat.");
        }

        $memberGuids = $this->roomRepository->getRoomMembers(
            roomGuid: $roomGuid,
            limit: $first ?? 12
        );

        return array_map(
            function (int $memberGuid) {
                $user = $this->entitiesBuilder->single($memberGuid);
                if (!$user) {
                    return null;
                }

                return new ChatRoomMemberEdge(
                    node: new UserNode(
                        user: $user
                    ),
                );
            },
            iterator_to_array($memberGuids)
        );
    }

    /**
     * @param int $roomGuid
     * @param User $loggedInUser
     * @return ChatRoomEdge
     * @throws ForbiddenException
     * @throws ServerErrorException
     * @throws ChatRoomNotFoundException
     */
    public function getRoom(
        int $roomGuid,
        User $loggedInUser
    ): ChatRoomEdge {
        if (
            !$this->roomRepository->isUserMemberOfRoom(
                roomGuid: $roomGuid,
                user: $loggedInUser
            )
        ) {
            throw new ForbiddenException("You are not a member of this chat.");
        }

        return new ChatRoomEdge(
            node: new ChatRoomNode(
                chatRoom: $this->roomRepository->getRoom($roomGuid)
            )
        );
    }
}
