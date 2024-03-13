<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use DateTimeImmutable;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Entities\ChatRoomListItem;
use Minds\Core\Chat\Enums\ChatRoomInviteRequestActionEnum;
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
use Minds\Core\Security\Block\BlockEntry;
use Minds\Core\Security\Block\BlockLimitException;
use Minds\Core\Security\Block\Manager as BlockManager;
use Minds\Core\Subscriptions\Relational\Repository as SubscriptionsRepository;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

class RoomService
{
    public function __construct(
        private readonly RoomRepository          $roomRepository,
        private readonly SubscriptionsRepository $subscriptionsRepository,
        private readonly EntitiesBuilder         $entitiesBuilder,
        private readonly BlockManager            $blockManager
    ) {
    }

    /**
     * @param User $user
     * @param array $otherMemberGuids
     * @param ChatRoomTypeEnum|null $roomType
     * @return ChatRoomEdge
     * @throws ChatRoomNotFoundException
     * @throws InvalidChatRoomTypeException
     * @throws ServerErrorException
     */
    public function createRoom(
        User              $user,
        array             $otherMemberGuids,
        ?ChatRoomTypeEnum $roomType = null
    ): ChatRoomEdge {
        if ($roomType === ChatRoomTypeEnum::GROUP_OWNED) {
            throw new InvalidChatRoomTypeException();
        }

        if (!$roomType) {
            $roomType = count($otherMemberGuids) > 1 ? ChatRoomTypeEnum::MULTI_USER : ChatRoomTypeEnum::ONE_TO_ONE;
        }

        if ($roomType === ChatRoomTypeEnum::ONE_TO_ONE) {
            if (count($otherMemberGuids) > 1) {
                throw new InvalidChatRoomTypeException("One to one rooms can only have 2 members.");
            }

            try {
                if ($chatRoom = $this->roomRepository->getOneToOneRoomByMembers(
                    firstMemberGuid: (int) $user->getGuid(),
                    secondMemberGuid: (int) $otherMemberGuids[0]
                )) {
                    return new ChatRoomEdge(
                        node: new ChatRoomNode(chatRoom: $chatRoom)
                    );
                }
            } catch (ChatRoomNotFoundException $e) {
                // Continue
            }
        }

        $chatRoom = new ChatRoom(
            guid: (int)Guid::build(),
            roomType: $roomType,
            createdByGuid: (int)$user->getGuid(),
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
                memberGuid: (int)$user->getGuid(),
                status: ChatRoomMemberStatusEnum::ACTIVE,
                role: ChatRoomRoleEnum::OWNER,
            );

            foreach ($otherMemberGuids as $memberGuid) {
                $isSubscribed = $this->subscriptionsRepository->isSubscribed(
                    userGuid: (int)$memberGuid,
                    friendGuid: (int)$user->getGuid()
                );

                $this->roomRepository->addRoomMember(
                    roomGuid: $chatRoom->guid,
                    memberGuid: (int)$memberGuid,
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
     * @return ChatRoomEdge
     * @throws ServerErrorException
     */
    public function createGroupOwnedRoom(User $user, int $groupGuid): ChatRoomEdge
    {
        $chatRoom = new ChatRoom(
            guid: (int)Guid::build(),
            roomType: ChatRoomTypeEnum::GROUP_OWNED,
            createdByGuid: (int)$user->getGuid(),
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
     * @param int $first
     * @param string|null $after
     * @param bool $hasMore
     * @return array<ChatRoomEdge>
     * @throws ServerErrorException
     */
    public function getRoomsByMember(
        User    $user,
        int     $first = 12,
        ?string $after = null,
        bool    &$hasMore = false
    ): array {
        $chatRooms = $this->roomRepository->getRoomsByMember(
            user: $user,
            limit: $first,
            offset: $after ? base64_decode($after, true) : null,
            hasMore: $hasMore
        );

        return array_map(
            fn (ChatRoomListItem $chatRoomListItem) => new ChatRoomEdge(
                node: new ChatRoomNode(
                    chatRoom: $chatRoomListItem->chatRoom
                ),
                cursor: $chatRoomListItem->lastMessageCreatedTimestamp ?
                    base64_encode((string)$chatRoomListItem->lastMessageCreatedTimestamp) :
                    base64_encode("0:{$chatRoomListItem->chatRoom->createdAt->getTimestamp()}"),
                lastMessagePlainText: $chatRoomListItem->lastMessagePlainText,
                lastMessageCreatedTimestamp: $chatRoomListItem->lastMessageCreatedTimestamp
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
     * @param string|null $after
     * @param bool $hasMore
     * @return array
     * @throws ForbiddenException
     * @throws ServerErrorException
     */
    public function getRoomMembers(
        int     $roomGuid,
        User    $loggedInUser,
        ?int    $first = null,
        ?string $after = null,
        bool    &$hasMore = false
    ): array {
        if (
            !$this->roomRepository->isUserMemberOfRoom(
                roomGuid: $roomGuid,
                user: $loggedInUser
            )
        ) {
            throw new ForbiddenException("You are not a member of this chat.");
        }

        // TODO: Filter out blocked, deleted, disabled and banned users

        $memberGuids = $this->roomRepository->getRoomMembers(
            roomGuid: $roomGuid,
            user: $loggedInUser,
            limit: $first ?? 12,
            offset: $after ? (int)base64_decode($after, true) : null,
            hasMore: $hasMore
        );

        return array_map(
            function (array $member): ?ChatRoomMemberEdge {
                $user = $this->entitiesBuilder->single($member['member_guid']);
                if (!$user) {
                    return null;
                }

                return new ChatRoomMemberEdge(
                    node: new UserNode(
                        user: $user
                    ),
                    cursor: base64_encode($member['joined_timestamp'] ?? "0")
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
        int  $roomGuid,
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

    /**
     * @param User $user
     * @return array<ChatRoomEdge>
     * @throws ServerErrorException
     */
    public function getRoomInviteRequestsByMember(
        User $user
    ): array {
        $chatRooms = $this->roomRepository->getRoomsByMember(
            user: $user,
            memberStatus: ChatRoomMemberStatusEnum::INVITE_PENDING
        );

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

    /**
     * @param User $user
     * @return int
     * @throws ServerErrorException
     */
    public function getTotalRoomInviteRequestsByMember(
        User $user
    ): int {
        return $this->roomRepository->getTotalRoomInviteRequestsByMember(
            user: $user
        );
    }

    /**
     * @param User $user
     * @param int $roomGuid
     * @param ChatRoomInviteRequestActionEnum $chatRoomInviteRequestAction
     * @return bool
     * @throws BlockLimitException
     * @throws ChatRoomNotFoundException
     * @throws ForbiddenException
     * @throws ServerErrorException
     */
    public function replyToRoomInviteRequest(
        User                            $user,
        int                             $roomGuid,
        ChatRoomInviteRequestActionEnum $chatRoomInviteRequestAction
    ): bool {
        $chatRoomEdge = $this->getRoom(
            roomGuid: $roomGuid,
            loggedInUser: $user
        );

        $this->roomRepository->beginTransaction();

        try {
            $this->roomRepository->updateRoomMemberStatus(
                roomGuid: $roomGuid,
                user: $user,
                memberStatus: $chatRoomInviteRequestAction === ChatRoomInviteRequestActionEnum::ACCEPT ?
                    ChatRoomMemberStatusEnum::ACTIVE :
                    ChatRoomMemberStatusEnum::LEFT
            );

            if ($chatRoomInviteRequestAction === ChatRoomInviteRequestActionEnum::REJECT_AND_BLOCK) {
                $this->blockManager->add(
                    (new BlockEntry())
                        ->setActor($user)
                        ->setSubjectGuid($chatRoomEdge->getNode()->chatRoom->createdByGuid)
                );
            }

            $this->roomRepository->commitTransaction();

            return true;
        } catch (ServerErrorException|BlockLimitException $e) {
            $this->roomRepository->rollbackTransaction();
            throw $e;
        }
    }
}
