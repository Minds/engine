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
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

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
     * @throws GraphQLException
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

        // TODO: Check with Mark if we need a minimum of 3 members for multi user rooms
        if ($roomType === ChatRoomTypeEnum::MULTI_USER && count($otherMemberGuids) < 2) {
            throw new InvalidChatRoomTypeException("Multi user rooms must have at least 3 members.");
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
                // TODO: Check if the user is blocked, deleted, disabled or banned
                // TODO: Check if user has blocked message sender

                // Check if user exists in minds|tenant
                $member = $this->entitiesBuilder->single($memberGuid);
                if (!$member) {
                    throw new GraphQLException(message: "One or more of the members you have selected was not found", code: 404);
                }

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
     * @return array{edges: ChatRoomEdge[], hasMore: bool}
     * @throws ServerErrorException
     */
    public function getRoomsByMember(
        User    $user,
        int     $first = 12,
        ?string $after = null
    ): array {
        ['chatRooms' => $chatRooms, 'hasMore' => $hasMore] = $this->roomRepository->getRoomsByMember(
            user: $user,
            targetMemberStatuses: [ChatRoomMemberStatusEnum::ACTIVE->name],
            limit: $first,
            offset: $after ? base64_decode($after, true) : null
        );

        return [
            'edges' => array_map(
                fn (ChatRoomListItem $chatRoomListItem) => new ChatRoomEdge(
                    node: new ChatRoomNode(
                        chatRoom: $chatRoomListItem->chatRoom
                    ),
                    cursor: $chatRoomListItem->lastMessageCreatedTimestamp ?
                        base64_encode((string)$chatRoomListItem->lastMessageCreatedTimestamp) :
                        base64_encode("0:{$chatRoomListItem->chatRoom->createdAt->getTimestamp()}"),
                    lastMessagePlainText: $chatRoomListItem->lastMessagePlainText,
                    lastMessageCreatedTimestamp: $chatRoomListItem->lastMessageCreatedTimestamp,
                    unreadMessagesCount: $chatRoomListItem->unreadMessagesCount,
                ),
                $chatRooms
            ),
            'hasMore' => $hasMore
        ];
    }

    /**
     * @param int $roomGuid
     * @return int
     * @throws ServerErrorException
     */
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
     * @param bool $excludeSelf
     * @return array
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    public function getRoomMembers(
        int     $roomGuid,
        User    $loggedInUser,
        ?int    $first = null,
        ?string $after = null,
        bool    $excludeSelf = true
    ): array {
        if (
            !$this->roomRepository->isUserMemberOfRoom(
                roomGuid: $roomGuid,
                user: $loggedInUser,
                targetStatuses: [
                    ChatRoomMemberStatusEnum::ACTIVE->name,
                    ChatRoomMemberStatusEnum::INVITE_PENDING->name
                ]
            )
        ) {
            throw new GraphQLException(message: "You are not a member of this chat.", code: 403);
        }

        // TODO: Filter out blocked, deleted, disabled and banned users

        ['members' => $members, 'hasMore' => $hasMore] = $this->roomRepository->getRoomMembers(
            roomGuid: $roomGuid,
            user: $loggedInUser,
            limit: $first ?? 12,
            offset: $after ? (int)base64_decode($after, true) : null,
            excludeSelf: $excludeSelf
        );

        return [
            'edges' => array_map(
                function (array $member): ?ChatRoomMemberEdge {
                    $user = $this->entitiesBuilder->single($member['member_guid']);
                    if (!$user) {
                        return null;
                    }

                    return new ChatRoomMemberEdge(
                        node: new UserNode(
                            user: $user
                        ),
                        role: constant(ChatRoomRoleEnum::class . '::' . $member['role_id']),
                        cursor: base64_encode($member['joined_timestamp'] ?? "0")
                    );
                },
                $members
            ),
            'hasMore' => $hasMore
        ];
    }

    /**
     * @param int $roomGuid
     * @param User $loggedInUser
     * @return ChatRoomEdge
     * @throws ChatRoomNotFoundException
     * @throws GraphQLException
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function getRoom(
        int  $roomGuid,
        User $loggedInUser
    ): ChatRoomEdge {
        if (
            !$this->roomRepository->isUserMemberOfRoom(
                roomGuid: $roomGuid,
                user: $loggedInUser,
                targetStatuses: [
                    ChatRoomMemberStatusEnum::ACTIVE->name,
                    ChatRoomMemberStatusEnum::INVITE_PENDING->name
                ]
            )
        ) {
            throw new GraphQLException(message: "You are not a member of this chat.", code: 403);
        }

        ['chatRooms' => $chatRooms] = $this->roomRepository->getRoomsByMember(
            user: $loggedInUser,
            targetMemberStatuses: [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ],
            limit: 1,
            roomGuid: $roomGuid
        );

        $chatRoomListItem = $chatRooms[0] ?? throw new ChatRoomNotFoundException();

        return new ChatRoomEdge(
            node: new ChatRoomNode(
                chatRoom: $chatRoomListItem->chatRoom,
                isChatRequest: $this->roomRepository->getUserStatusInRoom(
                    user: $loggedInUser,
                    roomGuid: $roomGuid
                ) === ChatRoomMemberStatusEnum::INVITE_PENDING,
                isUserRoomOwner: $this->roomRepository->isUserRoomOwner(
                    roomGuid: $roomGuid,
                    user: $loggedInUser
                ),
                areChatRoomNotificationsMuted: (bool) mt_rand(0, 1) // TODO: Fetch notifications status for room from db
            ),
            cursor: $chatRoomListItem->lastMessageCreatedTimestamp ?
                base64_encode((string)$chatRoomListItem->lastMessageCreatedTimestamp) :
                base64_encode("0:{$chatRoomListItem->chatRoom->createdAt->getTimestamp()}"),
            lastMessagePlainText: $chatRoomListItem->lastMessagePlainText,
            lastMessageCreatedTimestamp: $chatRoomListItem->lastMessageCreatedTimestamp
        );
    }

    /**
     * @param User $user
     * @param int $first
     * @param string|null $after
     * @return array<ChatRoomEdge>
     * @throws ServerErrorException
     */
    public function getRoomInviteRequestsByMember(
        User $user,
        int     $first = 12,
        ?string $after = null
    ): array {
        ['chatRooms' => $chatRooms, 'hasMore' => $hasMore] = $this->roomRepository->getRoomsByMember(
            user: $user,
            targetMemberStatuses: [ChatRoomMemberStatusEnum::INVITE_PENDING->name],
            limit: $first,
            offset: $after ? base64_decode($after, true) : null
        );

        return [
            'edges' => array_map(
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
                $chatRooms
            ),
            'hasMore' => $hasMore
        ];
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
     * @throws GraphQLException
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function replyToRoomInviteRequest(
        User                            $user,
        int                             $roomGuid,
        ChatRoomInviteRequestActionEnum $chatRoomInviteRequestAction
    ): bool {
        if (
            $this->roomRepository->getUserStatusInRoom(
                user: $user,
                roomGuid: $roomGuid
            ) !== ChatRoomMemberStatusEnum::INVITE_PENDING
        ) {
            throw new GraphQLException(message: "You have already responded to this request.", code: 400);
        }

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

            if (
                $chatRoomEdge->getNode()->chatRoom->roomType === ChatRoomTypeEnum::ONE_TO_ONE &&
                $chatRoomInviteRequestAction === ChatRoomInviteRequestActionEnum::REJECT_AND_BLOCK
            ) {
                $this->blockManager->add(
                    (new BlockEntry())
                        ->setActor($user)
                        ->setSubjectGuid($chatRoomEdge->getNode()->chatRoom->createdByGuid)
                );
            }

            if (
                $chatRoomInviteRequestAction === ChatRoomInviteRequestActionEnum::REJECT ||
                $chatRoomInviteRequestAction === ChatRoomInviteRequestActionEnum::REJECT_AND_BLOCK
            ) {
                $this->roomRepository->deleteRoom($roomGuid);
            }

            $this->roomRepository->commitTransaction();

            return true;
        } catch (ServerErrorException|BlockLimitException $e) {
            $this->roomRepository->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param User $user
     * @param int $roomGuid
     * @return bool
     * @throws ServerErrorException
     */
    public function isUserMemberOfRoom(
        User $user,
        int  $roomGuid
    ): bool {
        return $this->roomRepository->isUserMemberOfRoom(
            roomGuid: $roomGuid,
            user: $user,
            targetStatuses: [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ]
        );
    }

    /**
     * @param int $roomGuid
     * @param User $user
     * @return bool
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    public function deleteChatRoom(
        int $roomGuid,
        User $user
    ): bool {
        if (!$this->roomRepository->isUserRoomOwner(
            roomGuid: $roomGuid,
            user: $user
        )) {
            throw new GraphQLException(message: "You are not the owner of this chat.", code: 403);
        }

        $this->roomRepository->beginTransaction();
        try {
            $results = $this->roomRepository->deleteRoom($roomGuid);
            $this->roomRepository->commitTransaction();
            return $results;
        } catch (ServerErrorException $e) {
            $this->roomRepository->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param int $roomGuid
     * @param User $user
     * @return bool
     * @throws ServerErrorException
     */
    public function leaveChatRoom(
        int $roomGuid,
        User $user
    ): bool {
        return $this->roomRepository->updateRoomMemberStatus(
            roomGuid: $roomGuid,
            user: $user,
            memberStatus: ChatRoomMemberStatusEnum::LEFT
        );
    }

    /**
     * @param int $roomGuid
     * @param int $memberGuid
     * @param User $user
     * @return bool
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    public function removeMemberFromChatRoom(
        int $roomGuid,
        int $memberGuid,
        User $user
    ): bool {
        if (!$this->roomRepository->isUserRoomOwner(
            roomGuid: $roomGuid,
            user: $user
        )) {
            throw new GraphQLException(message: "You are not the owner of this chat.", code: 403);
        }

        return $this->roomRepository->updateRoomMemberStatus(
            roomGuid: $roomGuid,
            user: $this->entitiesBuilder->single($memberGuid),
            memberStatus: ChatRoomMemberStatusEnum::LEFT
        );
    }

    /**
     * @param int $roomGuid
     * @param User $user
     * @return bool
     * @throws BlockLimitException
     * @throws ChatRoomNotFoundException
     * @throws GraphQLException
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function deleteChatRoomAndBlockUser(
        int $roomGuid,
        User $user
    ): bool {
        $chatRoomEdge = $this->getRoom(
            roomGuid: $roomGuid,
            loggedInUser: $user
        );

        if ($chatRoomEdge->getNode()->getRoomType() !== ChatRoomTypeEnum::ONE_TO_ONE) {
            throw new GraphQLException(message: "You can only block users in one-to-one rooms", code: 400);
        }

        $memberGuid = $this->getRoomMembers(
            roomGuid: $roomGuid,
            loggedInUser: $user,
            first: 1
        )['edges'][0]->getNode()->getGuid();

        if (!$this->deleteChatRoom(
            roomGuid: $roomGuid,
            user: $user
        )) {
            throw new GraphQLException(message: "Failed to block user", code: 500);
        }

        if (!$this->blockManager->add(
            (new BlockEntry())
                ->setActor($user)
                ->setSubjectGuid($memberGuid)
        )) {
            throw new GraphQLException(message: "Failed to block user", code: 500);
        }

        return true;
    }
}
