<?php

namespace Minds\Core\Chat\Controllers;

use InvalidArgumentException;
use Minds\Core\Chat\Enums\ChatRoomInviteRequestActionEnum;
use Minds\Core\Chat\Enums\ChatRoomNotificationStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Exceptions\ChatMessageNotFoundException;
use Minds\Core\Chat\Exceptions\ChatRoomNotFoundException;
use Minds\Core\Chat\Exceptions\InvalidChatRoomTypeException;
use Minds\Core\Chat\Services\MessageService;
use Minds\Core\Chat\Services\ReceiptService;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Chat\Types\ChatMessageEdge;
use Minds\Core\Chat\Types\ChatMessagesConnection;
use Minds\Core\Chat\Types\ChatRoomEdge;
use Minds\Core\Chat\Types\ChatRoomMemberNode;
use Minds\Core\Chat\Types\ChatRoomMembersConnection;
use Minds\Core\Chat\Types\ChatRoomsConnection;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\Block\BlockLimitException;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class ChatController
{
    public function __construct(
        private readonly RoomService $roomService,
        private readonly MessageService $messageService,
        private readonly ReceiptService $receiptService,
    ) {
    }

    /**
     * Returns a list of chat rooms available to a user
     */
    #[Query]
    #[Logged]
    public function getChatRoomList(
        int                 $first = 12,
        ?string             $after = null,
        #[InjectUser] ?User $loggedInUser = null,
    ): ChatRoomsConnection {
        $connection = new ChatRoomsConnection();

        ['edges' => $chatRoomEdges, 'hasMore' => $hasMore] = $this->roomService->getRoomsByMember(
            user: $loggedInUser,
            first: $first,
            after: $after
        );

        $connection->setEdges($chatRoomEdges);

        $lastEdgeIndex = count($connection->getEdges()) > 0 ? count($connection->getEdges()) - 1 : null;

        $connection->setPageInfo(new PageInfo(
            hasNextPage: $hasMore,
            hasPreviousPage: $after ?: false,
            startCursor: $after,
            endCursor: $lastEdgeIndex !== null ? $connection->getEdges()[$lastEdgeIndex]->getCursor() : null,
        ));

        return $connection;
    }

    /**
     * @deprecated
     * @param User $loggedInUser
     * @return string[]
     */
    #[Query]
    #[Logged]
    public function getChatRoomGuids(
        #[InjectUser] User $loggedInUser,
    ): array {
        return [];
    }

    /**
     * Returns a chat room
     */
    #[Query]
    #[Logged]
    public function getChatRoom(
        string             $roomGuid,
        #[InjectUser] User $loggedInUser,
    ): ChatRoomEdge {
        return $this->roomService->getRoom(
            roomGuid: (int)$roomGuid,
            loggedInUser: $loggedInUser
        );
    }

    /**
     * Returns a list of messages for a given chat room
     */
    #[Query]
    #[Logged]
    public function getChatMessages(
        string             $roomGuid,
        #[InjectUser] User $loggedInUser,
        int                $first = 12,
        ?string            $after = null,
        ?string            $before = null,
    ): ChatMessagesConnection {
        if ($after && $before) {
            throw new InvalidArgumentException('You cannot use both "after" and "before" parameters at the same time');
        }

        $connection = new ChatMessagesConnection();

        ['edges' => $edges, 'hasMore' => $hasMore] = $this->messageService->getMessages(
            roomGuid: (int)$roomGuid,
            user: $loggedInUser,
            first: $first,
            after: $before, // we need to reverse the order of the messages
            before: $after, // we need to reverse the order of the messages
        );

        $connection->setEdges($edges);

        $startCursor = $endCursor = null;

        $hasNextPage = $hasPreviousPage = false;

        $lastEdgeIndex = count($connection->getEdges()) > 0 ? count($connection->getEdges()) - 1 : null;

        if ($lastEdgeIndex === null) { // no messages
            $connection->setPageInfo(new PageInfo(
                hasNextPage: false,
                hasPreviousPage: false,
                startCursor: null,
                endCursor: null,
            ));
            return $connection;
        }

        if (!$before && !$after) { // initial scenario for pagination
            $hasPreviousPage = $hasMore;
            $startCursor = $connection->getEdges()[0]->getCursor();
            $endCursor = $connection->getEdges()[$lastEdgeIndex]->getCursor();
        } elseif ($before) { // we are paginating backwards
            $hasPreviousPage = $hasMore;
            $hasNextPage = true;
            $startCursor = $connection->getEdges()[0]->getCursor();
            $endCursor = $connection->getEdges()[$lastEdgeIndex]->getCursor();
        } elseif ($after) { // we are paginating forwards
            $hasPreviousPage = true;
            $hasNextPage = $hasMore;
            $startCursor = $connection->getEdges()[0]->getCursor();
            $endCursor = $connection->getEdges()[$lastEdgeIndex]->getCursor();
        }

        $connection->setPageInfo(new PageInfo(
            hasNextPage: $hasNextPage,
            hasPreviousPage: $hasPreviousPage,
            startCursor: $startCursor,
            endCursor: $endCursor,
        ));

        return $connection;
    }

    /**
     * Returns the members of a chat room
     */
    #[Query]
    #[Logged]
    public function getChatRoomMembers(
        #[InjectUser] User $loggedInUser,
        ?string            $roomGuid = null,
        ?int               $first = null,
        ?string            $after = null,
        ?int               $last = null,
        ?int               $before = null,
        bool               $excludeSelf = true
    ): ChatRoomMembersConnection {
        $connection = new ChatRoomMembersConnection();

        ['edges' => $chatRoomMemberEdges, 'hasMore' => $hasMore] = $this->roomService->getRoomMembers(
            roomGuid: (int)$roomGuid,
            loggedInUser: $loggedInUser,
            first: $first,
            after: $after,
            excludeSelf: $excludeSelf
        );

        $connection->setEdges($chatRoomMemberEdges);

        $lastEdgeIndex = count($connection->getEdges()) > 0 ? count($connection->getEdges()) - 1 : null;

        $connection->setPageInfo(new PageInfo(
            hasNextPage: $hasMore,
            hasPreviousPage: $after ?: false,
            startCursor: $after,
            endCursor: $lastEdgeIndex !== null ? $connection->getEdges()[$lastEdgeIndex]->getCursor() : null,
        ));

        return $connection;
    }

    /**
     * Creates a new chat room
     * @param string[] $otherMemberGuids
     * @param ChatRoomTypeEnum|null $roomType
     * @return ChatRoomEdge
     * @throws GraphQLException
     * @throws InvalidChatRoomTypeException
     * @throws ServerErrorException
     */
    #[Mutation]
    #[Logged]
    public function createChatRoom(
        #[InjectUser] User $loggedInUser,
        array              $otherMemberGuids = [],
        ?ChatRoomTypeEnum  $roomType = null,
        string             $groupGuid = null,
    ): ChatRoomEdge {
        return $this->roomService->createRoom(
            user: $loggedInUser,
            otherMemberGuids: $otherMemberGuids,
            roomType: $roomType,
            groupGuid: $groupGuid ? (int) $groupGuid : null,
        );
    }

    public function getChatRoomMembersCount(
        int $roomGuid,
    ): int {
        return $this->roomService->getRoomTotalMembers($roomGuid);
    }

    /**
     * Creates a new message in a chat room
     */
    #[Mutation]
    #[Logged]
    public function createChatMessage(
        string             $plainText,
        string             $roomGuid,
        #[InjectUser] User $loggedInUser,
    ): ChatMessageEdge {
        return $this->messageService->addMessage(
            roomGuid: (int)$roomGuid,
            user: $loggedInUser,
            message: $plainText
        );
    }

    /**
     * @param int $first
     * @param string|null $after
     * @return ChatRoomsConnection
     * @throws ServerErrorException
     */
    #[Query]
    #[Logged]
    public function getChatRoomInviteRequests(
        #[InjectUser] User $loggedInUser,
        int                $first = 12,
        ?string            $after = null,
    ): ChatRoomsConnection {
        $connection = new ChatRoomsConnection();

        ['edges' => $chatRoomEdges, 'hasMore' => $hasMore] = $this->roomService->getRoomInviteRequestsByMember(
            user: $loggedInUser,
            first: $first,
            after: $after
        );

        $connection->setEdges($chatRoomEdges);

        $lastEdgeIndex = count($connection->getEdges()) > 0 ? count($connection->getEdges()) - 1 : null;

        $connection->setPageInfo(new PageInfo(
            hasNextPage: $hasMore,
            hasPreviousPage: $after ?: false,
            startCursor: $after,
            endCursor: $lastEdgeIndex !== null ? $connection->getEdges()[$lastEdgeIndex]->getCursor() : null,
        ));

        return $connection;
    }

    #[Query]
    #[Logged]
    public function getTotalRoomInviteRequests(
        #[InjectUser] User $loggedInUser,
    ): int {
        return $this->roomService->getTotalRoomInviteRequestsByMember(
            user: $loggedInUser
        );
    }

    /**
     * @param string $roomGuid
     * @param ChatRoomInviteRequestActionEnum $chatRoomInviteRequestActionEnum
     * @return bool
     * @throws ServerErrorException
     * @throws ChatRoomNotFoundException
     * @throws ForbiddenException
     * @throws BlockLimitException
     */
    #[Mutation]
    #[Logged]
    public function replyToRoomInviteRequest(
        string                          $roomGuid,
        ChatRoomInviteRequestActionEnum $chatRoomInviteRequestActionEnum,
        #[InjectUser] User              $loggedInUser
    ): bool {
        return $this->roomService->replyToRoomInviteRequest(
            user: $loggedInUser,
            roomGuid: (int) $roomGuid,
            chatRoomInviteRequestAction: $chatRoomInviteRequestActionEnum
        );
    }

    /**
     * Returns the total count of unread messages a user has
     */
    #[Query]
    #[Logged]
    public function getChatUnreadMessagesCount(
        #[InjectUser] User $loggedInUser,
    ): int {
        return $this->receiptService->getAllUnreadMessagesCount($loggedInUser);
    }

    /**
     * Updates the read receipt of a room
     */
    #[Mutation]
    #[Logged]
    public function readReceipt(
        string $roomGuid,
        string $messageGuid,
        #[InjectUser] User $loggedInUser,
    ): ChatRoomEdge {
        $room = $this->roomService->getRoom($roomGuid, $loggedInUser);
        $message = $this->messageService->getMessage($roomGuid, $messageGuid, $loggedInUser);

        $this->receiptService->updateReceipt($message, $loggedInUser);

        $room->unreadMessagesCount = 0;
        return $room;
    }

    /**
     * @param string $roomGuid
     * @param string $messageGuid
     * @return bool
     * @throws ServerErrorException
     * @throws ChatMessageNotFoundException
     * @throws GraphQLException
     */
    #[Mutation]
    #[Logged]
    public function deleteChatMessage(
        string $roomGuid,
        string $messageGuid,
        #[InjectUser] User $loggedInUser
    ): bool {
        return $this->messageService->deleteMessage(
            roomGuid: (int) $roomGuid,
            messageGuid: (int) $messageGuid,
            loggedInUser: $loggedInUser
        );
    }

    /**
     * @param string $roomGuid
     * @return bool
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    #[Mutation]
    #[Logged]
    public function deleteChatRoom(
        string $roomGuid,
        #[InjectUser] User $loggedInUser
    ): bool {
        return $this->roomService->deleteChatRoomByRoomGuid(
            roomGuid: (int) $roomGuid,
            user: $loggedInUser
        );
    }

    /**
     * @param string $roomGuid
     * @return bool
     * @throws ServerErrorException
     */
    #[Mutation]
    #[Logged]
    public function leaveChatRoom(
        string $roomGuid,
        #[InjectUser] User $loggedInUser
    ): bool {
        return $this->roomService->leaveChatRoom(
            roomGuid: (int) $roomGuid,
            user: $loggedInUser
        );
    }

    /**
     * Add members to a chat room.
     * @param string $roomGuid - The guid of the room.
     * @param array<string> $memberGuids - The guids of the members to add.
     * @return bool - True if the members were added successfully.
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    #[Mutation]
    #[Logged]
    public function addMembersToChatRoom(
        string $roomGuid,
        array $memberGuids,
        #[InjectUser] User $loggedInUser
    ): bool {
        return $this->roomService->addRoomMembers(
            roomGuid: (int) $roomGuid,
            memberGuids: $memberGuids,
            user: $loggedInUser
        );
    }

    /**
     * @param string $roomGuid
     * @param string $memberGuid
     * @return bool
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    #[Mutation]
    #[Logged]
    public function removeMemberFromChatRoom(
        string $roomGuid,
        string $memberGuid,
        #[InjectUser] User $loggedInUser
    ): bool {
        return $this->roomService->removeMemberFromChatRoom(
            roomGuid: (int) $roomGuid,
            memberGuid: (int) $memberGuid,
            user: $loggedInUser
        );
    }

    /**
     * @param string $roomGuid
     * @return bool
     * @throws BlockLimitException
     * @throws ChatRoomNotFoundException
     * @throws GraphQLException
     * @throws ServerErrorException
     * @throws NotFoundException
     */
    #[Mutation]
    #[Logged]
    public function deleteChatRoomAndBlockUser(
        string $roomGuid,
        #[InjectUser] User $loggedInUser
    ): bool {
        return $this->roomService->deleteChatRoomAndBlockUser(
            roomGuid: (int) $roomGuid,
            user: $loggedInUser
        );
    }

    /**
     * @param string $roomGuid
     * @param ChatRoomNotificationStatusEnum $notificationStatus
     * @return bool
     */
    #[Mutation]
    #[Logged]
    public function updateNotificationSettings(
        string $roomGuid,
        ChatRoomNotificationStatusEnum $notificationStatus,
        #[InjectUser] User $loggedInUser
    ): bool {
        return $this->roomService->updateRoomMemberSettings(
            roomGuid: (int) $roomGuid,
            user: $loggedInUser,
            notificationStatus: $notificationStatus
        );
    }

    /**
     * Update chat room name.
     * @param string $roomGuid - The guid of the room.
     * @param string $roomName - The new name of the room.
     * @param User $loggedInUser - The user updating the room name.
     * @return bool - True if the room name was updated successfully.
     */
    #[Mutation]
    #[Logged]
    public function updateChatRoomName(
        string $roomGuid,
        string $roomName,
        #[InjectUser] User $loggedInUser
    ): bool {
        return $this->roomService->updateRoomName(
            roomGuid: (int) $roomGuid,
            roomName: $roomName,
            user: $loggedInUser
        );
    }
}
