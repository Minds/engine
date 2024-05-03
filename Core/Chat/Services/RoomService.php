<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use DateTimeImmutable;
use Minds\Core\Chat\Delegates\AnalyticsDelegate;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Entities\ChatRoomListItem;
use Minds\Core\Chat\Enums\ChatRoomInviteRequestActionEnum;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomNotificationStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomRoleEnum;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Exceptions\ChatRoomNotFoundException;
use Minds\Core\Chat\Exceptions\InvalidChatRoomTypeException;
use Minds\Core\Chat\Helpers\ChatRoomEdgeCursorHelper;
use Minds\Core\Chat\Helpers\ChatRoomMemberEdgeCursorHelper;
use Minds\Core\Chat\Repositories\RoomRepository;
use Minds\Core\Chat\Types\ChatRoomEdge;
use Minds\Core\Chat\Types\ChatRoomMemberEdge;
use Minds\Core\Chat\Types\ChatRoomNode;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\GraphQL\Types\UserNode;
use Minds\Core\Guid;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipManager;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\Block\BlockEntry;
use Minds\Core\Security\Block\BlockLimitException;
use Minds\Core\Security\Block\Manager as BlockManager;
use Minds\Core\Subscriptions\Relational\Repository as SubscriptionsRepository;
use Minds\Entities\Group;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class RoomService
{
    public function __construct(
        private readonly RoomRepository          $roomRepository,
        private readonly SubscriptionsRepository $subscriptionsRepository,
        private readonly EntitiesBuilder         $entitiesBuilder,
        private readonly BlockManager            $blockManager,
        private readonly GroupMembershipManager  $groupMembershipManager,
        private readonly AnalyticsDelegate       $analyticsDelegate
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
        ?ChatRoomTypeEnum $roomType = null,
        int               $groupGuid = null,
    ): ChatRoomEdge {
        if ($roomType === ChatRoomTypeEnum::GROUP_OWNED) {
            // Check if a group room already exists
            $rooms = $this->getRoomsByGroup($groupGuid);

            if ($rooms) {
                $chatRoom = $rooms[0];
            } else {
                // Get the group entity
                $group = $this->entitiesBuilder->single($groupGuid);

                if (!$group instanceof Group) {
                    throw new UserErrorException("The provided group was not a group");
                }

                // Check if this user is a group admin
                $groupMembership = $this->groupMembershipManager->getMembership($group, $user);

                if (!$groupMembership->isOwner()) {
                    throw new ForbiddenException('Only group owners can create a group owned room');
                }

                $chatRoom = new ChatRoom(
                    guid: (int) Guid::build(),
                    roomType: $roomType,
                    createdByGuid: (int) $user->getGuid(),
                    createdAt: new DateTimeImmutable(),
                    groupGuid: $groupGuid,
                );

                $this->roomRepository->createRoom(
                    roomGuid: $chatRoom->guid,
                    roomType: $chatRoom->roomType,
                    createdByGuid: $chatRoom->createdByGuid,
                    createdAt: $chatRoom->createdAt,
                    groupGuid: $chatRoom->groupGuid,
                );
            }

            $chatRoom->setName($this->getRoomName($chatRoom, $user, []));

            return new ChatRoomEdge(
                node: new ChatRoomNode(chatRoom: $chatRoom)
            );
        } elseif ($groupGuid) {
            throw new UserErrorException('Can not pass a groupGuid to a non group roomType');
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
                    $chatRoom->setName($this->getRoomName($chatRoom, $user, $otherMemberGuids));
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

            $this->roomRepository->addRoomMemberDefaultSettings(
                roomGuid: $chatRoom->guid,
                memberGuid: (int)$user->getGuid(),
                notificationStatus: ChatRoomNotificationStatusEnum::ALL
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

                $this->roomRepository->addRoomMemberDefaultSettings(
                    roomGuid: $chatRoom->guid,
                    memberGuid: (int)$memberGuid,
                    notificationStatus: ChatRoomNotificationStatusEnum::ALL
                );

                // TODO: Add push notifications and emails
            }
        } catch (ServerErrorException $e) {
            $this->roomRepository->rollbackTransaction();
            throw $e;
        }

        $this->roomRepository->commitTransaction();

        $chatRoom->setName($this->getRoomName($chatRoom, $user, $otherMemberGuids));
        $this->analyticsDelegate->onChatRoomCreate(
            actor: $user,
            chatRoom: $chatRoom
        );

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
            groupGuid: $groupGuid,
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

        $this->analyticsDelegate->onChatRoomCreate(
            actor: $user,
            chatRoom: $chatRoom
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
        [
            'lastMessageCreatedAtTimestamp' => $lastMessageCreatedAtTimestamp,
            'roomCreatedAtTimestamp' => $roomCreatedAtTimestamp
        ] = ChatRoomEdgeCursorHelper::readCursor($after);

        ['chatRooms' => $chatRooms, 'hasMore' => $hasMore] = $this->roomRepository->getRoomsByMember(
            user: $user,
            targetMemberStatuses: [ChatRoomMemberStatusEnum::ACTIVE->name],
            limit: $first,
            lastMessageCreatedAtTimestamp: $lastMessageCreatedAtTimestamp ?? null,
            roomCreatedAtTimestamp: $roomCreatedAtTimestamp ?? null
        );

        return [
            'edges' => array_map(
                function (ChatRoomListItem $chatRoomListItem) use ($user) {
                    $chatRoom = $chatRoomListItem->chatRoom;
                    $chatRoom->setName($this->getRoomName($chatRoomListItem->chatRoom, $user, $chatRoomListItem->memberGuids));
                    return new ChatRoomEdge(
                        node: new ChatRoomNode(
                            chatRoom: $chatRoom,
                        ),
                        cursor: ChatRoomEdgeCursorHelper::generateCursor(
                            roomCreatedAtTimestamp: $chatRoomListItem->chatRoom->createdAt->getTimestamp(),
                            lastMessageCreatedAtTimestamp: $chatRoomListItem->lastMessageCreatedTimestamp
                        ),
                        lastMessagePlainText: $chatRoomListItem->lastMessagePlainText,
                        lastMessageCreatedTimestamp: $chatRoomListItem->lastMessageCreatedTimestamp,
                        unreadMessagesCount: $chatRoomListItem->unreadMessagesCount,
                    );
                },
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
     * @return array{edges: ChatRoomMemberEdge[], hasMore: bool}
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

        [
            'joinedTimestamp' => $offsetJoinedTimestamp,
            'memberGuid' => $offsetMemberGuid
        ] = ChatRoomMemberEdgeCursorHelper::readCursor($after);

        ['members' => $members, 'hasMore' => $hasMore] = $this->roomRepository->getRoomMembers(
            roomGuid: $roomGuid,
            user: $loggedInUser,
            limit: $first ?? 12,
            offsetJoinedTimestamp: $offsetJoinedTimestamp ?? null,
            offsetMemberGuid: $offsetMemberGuid ?? null,
            excludeSelf: $excludeSelf
        );

        return [
            'edges' => array_values(array_filter(array_map(
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
                        cursor: ChatRoomMemberEdgeCursorHelper::generateCursor(
                            memberGuid: (int)$member['member_guid'],
                            joinedTimestamp: (int)$member['joined_timestamp']
                        ),
                        notificationStatus: constant(ChatRoomNotificationStatusEnum::class . '::' . $member['notifications_status'])
                    );
                },
                $members
            ))),
            'hasMore' => $hasMore
        ];
    }

    /**
     * @param int $roomGuid
     * @param User $user
     * @param bool $excludeSelf
     * @return iterable<ChatRoomMemberEdge>
     * @throws ServerErrorException
     */
    public function getAllRoomMembers(
        int $roomGuid,
        User $user,
        bool $excludeSelf = true
    ): iterable {
        foreach ($this->roomRepository->getAllRoomMembers(roomGuid: $roomGuid, user: $user, excludeSelf: $excludeSelf) as $member) {
            $user = $this->entitiesBuilder->single($member['member_guid']);
            if (!$user) {
                continue;
            }

            yield new ChatRoomMemberEdge(
                node: new UserNode(
                    user: $user
                ),
                role: constant(ChatRoomRoleEnum::class . '::' . $member['role_id']),
                cursor: base64_encode($member['joined_timestamp'] ?? "0:{$member['member_guid']}"),
                notificationStatus: constant(ChatRoomNotificationStatusEnum::class . '::' . $member['notifications_status'])
            );
        }
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

        $chatRoomMemberSettings = $this->roomRepository->getRoomMemberSettings(
            roomGuid: $roomGuid,
            memberGuid: (int)$loggedInUser->getGuid()
        );

        $chatRoom = $chatRoomListItem->chatRoom;
        $chatRoom->setName($this->getRoomName($chatRoom, $loggedInUser, $chatRoomListItem->memberGuids));
        return new ChatRoomEdge(
            node: new ChatRoomNode(
                chatRoom: $chatRoom,
                isChatRequest: $chatRoom->roomType === ChatRoomTypeEnum::GROUP_OWNED ? false // groups do not have invite requests
                    : $this->roomRepository->getUserStatusInRoom(
                        user: $loggedInUser,
                        roomGuid: $roomGuid
                    ) === ChatRoomMemberStatusEnum::INVITE_PENDING,
                isUserRoomOwner: $this->roomRepository->isUserRoomOwner(
                    roomGuid: $roomGuid,
                    user: $loggedInUser
                ),
                chatRoomNotificationStatus:
                    isset($chatRoomMemberSettings['notifications_status']) ?
                        constant(ChatRoomNotificationStatusEnum::class . '::' . $chatRoomMemberSettings['notifications_status']) :
                        ChatRoomNotificationStatusEnum::ALL,
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
        [
            'lastMessageCreatedAtTimestamp' => $lastMessageCreatedAtTimestamp,
            'roomCreatedAtTimestamp' => $roomCreatedAtTimestamp
        ] = ChatRoomEdgeCursorHelper::readCursor($after);

        ['chatRooms' => $chatRooms, 'hasMore' => $hasMore] = $this->roomRepository->getRoomsByMember(
            user: $user,
            targetMemberStatuses: [ChatRoomMemberStatusEnum::INVITE_PENDING->name],
            limit: $first,
            lastMessageCreatedAtTimestamp: $lastMessageCreatedAtTimestamp ?? null,
            roomCreatedAtTimestamp: $roomCreatedAtTimestamp ?? null
        );

        return [
            'edges' => array_map(
                function (ChatRoomListItem $chatRoomListItem) use ($user) {
                    $chatRoom = $chatRoomListItem->chatRoom;
                    $chatRoom->setName($this->getRoomName($chatRoom, $user, $chatRoomListItem->memberGuids));
                    return  new ChatRoomEdge(
                        node: new ChatRoomNode(
                            chatRoom: $chatRoom,
                        ),
                        cursor: ChatRoomEdgeCursorHelper::generateCursor(
                            roomCreatedAtTimestamp: $chatRoomListItem->chatRoom->createdAt->getTimestamp(),
                            lastMessageCreatedAtTimestamp: $chatRoomListItem->lastMessageCreatedTimestamp
                        ),
                        lastMessagePlainText: $chatRoomListItem->lastMessagePlainText,
                        lastMessageCreatedTimestamp: $chatRoomListItem->lastMessageCreatedTimestamp
                    );
                },
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

            switch($chatRoomInviteRequestAction) {
                case ChatRoomInviteRequestActionEnum::ACCEPT:
                    $this->analyticsDelegate->onChatRequestAccept(
                        actor: $user,
                        chatRoom: $chatRoomEdge->getNode()->chatRoom
                    );
                    break;
                case ChatRoomInviteRequestActionEnum::REJECT:
                case ChatRoomInviteRequestActionEnum::REJECT_AND_BLOCK:
                    $this->analyticsDelegate->onChatRequestDecline(
                        actor: $user,
                        chatRoom: $chatRoomEdge->getNode()->chatRoom
                    );
                    break;
            }

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
    public function deleteChatRoomByRoomGuid(
        int $roomGuid,
        User $user
    ): bool {
        $chatRoomEdge = $this->getRoom(
            roomGuid: $roomGuid,
            loggedInUser: $user
        );

        if (!$chatRoomEdge?->getNode()?->chatRoom) {
            throw new GraphQLException(message: "Chat room could not be found.", code: 404);
        }

        return $this->deleteChatRoom($chatRoomEdge->getNode()->chatRoom, $user);
    }

    public function deleteChatRoom(
        ChatRoom $chatRoom,
        User $user
    ): bool {
        if (!$this->roomRepository->isUserRoomOwner(
            roomGuid: $chatRoom->guid,
            user: $user
        )) {
            throw new GraphQLException(message: "You are not the owner of this chat.", code: 403);
        }

        $this->roomRepository->beginTransaction();

        try {
            $results = $this->roomRepository->deleteRoom($chatRoom->guid);
            $this->roomRepository->commitTransaction();

            $this->analyticsDelegate->onChatRoomDelete(
                actor: $user,
                chatRoom: $chatRoom
            );

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
        $chatRoomEdge = $this->getRoom(
            roomGuid: $roomGuid,
            loggedInUser: $user
        );

        $success = $this->roomRepository->updateRoomMemberStatus(
            roomGuid: $roomGuid,
            user: $user,
            memberStatus: ChatRoomMemberStatusEnum::LEFT
        );

        $this->analyticsDelegate->onChatRoomLeave(
            actor: $user,
            chatRoom: $chatRoomEdge?->getNode()?->chatRoom
        );

        return $success;
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

        if (!$this->deleteChatRoomByRoomGuid(
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

    /**
     * @param int $roomGuid
     * @param User $user
     * @param ChatRoomNotificationStatusEnum $notificationStatus
     * @return bool
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    public function updateRoomMemberSettings(
        int $roomGuid,
        User $user,
        ChatRoomNotificationStatusEnum $notificationStatus
    ): bool {
        if (!$this->isUserMemberOfRoom(
            user: $user,
            roomGuid: $roomGuid
        )) {
            throw new GraphQLException(message: "You are not a member of this chat.", code: 403);
        }

        return $this->roomRepository->updateRoomMemberSettings(
            roomGuid: $roomGuid,
            memberGuid: (int) $user->getGuid(),
            notificationStatus: $notificationStatus
        );
    }

    /**
     * Get chat rooms by group guid.
     * @param integer $groupGuid - Group guid.
     * @return array - Chat rooms.
     */
    public function getRoomsByGroup(int $groupGuid): array
    {
        $chatRooms = $this->roomRepository->getGroupRooms($groupGuid);

        if (!count($chatRooms)) {
            return [];
        }

        return $chatRooms;
    }

    /**
     * Builds the name of the chat room
     * @param int[] $memberGuids
     */
    public function getRoomName(
        ChatRoom $chatRoom,
        User $currentUser,
        array $memberGuids = [],
    ): string {
        if ($chatRoom->name) {
            return $chatRoom->name;
        }

        if ($chatRoom->roomType === ChatRoomTypeEnum::GROUP_OWNED) {
            $group = $this->entitiesBuilder->single($chatRoom->groupGuid);

            if (!$group instanceof Group) {
                return 'Unkown group';
            }

            return (string) $group->getName();
        }

        $memberGuids = array_diff($memberGuids, [(int) $currentUser->getGuid()]);

        if (empty($memberGuids)) {
            return '';
        }
 
        $names = array_map(function ($guid) {
            $member = $this->entitiesBuilder->single($guid);

            if (!$member instanceof User) {
                return 'Unknown User';
            }

            return $member->getName();
        }, array_slice($memberGuids, 0, 3));

        $namesCount = count($names);

        if ($namesCount === 1) {
            return $names[0];
        } elseif ($namesCount === 2) {
            return "{$names[0]} & $names[1]";
        } else {
            return "{$names[0]}, $names[1] & {$names[2]}";
        }
    }
}
