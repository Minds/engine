<?php
namespace Minds\Core\Chat\Controllers;

use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Exceptions\InvalidChatRoomTypeException;
use Minds\Core\Chat\Services\MessageService;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Chat\Types\ChatMessageEdge;
use Minds\Core\Chat\Types\ChatMessagesConnection;
use Minds\Core\Chat\Types\ChatRoomEdge;
use Minds\Core\Chat\Types\ChatRoomMemberNode;
use Minds\Core\Chat\Types\ChatRoomMembersConnection;
use Minds\Core\Chat\Types\ChatRoomsConnection;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class ChatController
{
    public function __construct(
        private readonly RoomService $roomService,
        private readonly MessageService $messageService
    ) {
    }

    /**
     * Returns a list of chat rooms available to a user
     */
    #[Query]
    #[Logged]
    public function getChatRoomList(
        int $first = 12,
        ?string $after = null,
        #[InjectUser] ?User $loggedInUser = null,
    ): ChatRoomsConnection {
        $connection = new ChatRoomsConnection();
        $hasMore = false;

        $chatRoomEdges = $this->roomService->getRoomsByMember(
            user: $loggedInUser,
            first: $first,
            after: $after,
            hasMore: $hasMore
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
     * Returns a chat room
     */
    #[Query]
    #[Logged]
    public function getChatRoom(
        string $roomGuid,
        #[InjectUser] User $loggedInUser,
    ): ChatRoomEdge {
        return $this->roomService->getRoom(
            roomGuid: (int) $roomGuid,
            loggedInUser: $loggedInUser
        );
    }

    /**
     * Returns a list of messages for a given chat room
     */
    #[Query]
    #[Logged]
    public function getChatMessages(
        string $roomGuid,
        #[InjectUser] User $loggedInUser,
        ?int $first = null,
        ?int $after = null,
        ?int $last = null,
        ?int $before = null,
    ): ChatMessagesConnection {
        $connection = new ChatMessagesConnection();

        $connection->setEdges(
            $this->messageService->getMessages(
                roomGuid: (int) $roomGuid,
                limit: $first ?? 0,
                offset: $after ?? 0
            )
        );

        $connection->setPageInfo(new PageInfo(
            hasNextPage: false,
            hasPreviousPage: false,
            startCursor: null,
            endCursor: null,
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
        ?string $roomGuid = null,
        ?int $first = null,
        ?string $after = null,
        ?int $last = null,
        ?int $before = null,
    ): ChatRoomMembersConnection {
        $connection = new ChatRoomMembersConnection();
        $hasMore = false;

        $connection->setEdges(
            $this->roomService->getRoomMembers(
                roomGuid: (int) $roomGuid,
                loggedInUser: $loggedInUser,
                first: $first,
                after: $after,
                hasMore: $hasMore
            )
        );

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
     * @throws InvalidChatRoomTypeException
     * @throws ServerErrorException
     */
    #[Mutation]
    #[Logged]
    public function createChatRoom(
        #[InjectUser] User $loggedInUser,
        array $otherMemberGuids = [],
        ?ChatRoomTypeEnum $roomType = null
    ): ChatRoomEdge {
        return $this->roomService->createRoom(
            user: $loggedInUser,
            otherMemberGuids: $otherMemberGuids,
            roomType: $roomType
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
        string $plainText,
        string $roomGuid,
        #[InjectUser] User $loggedInUser,
    ): ChatMessageEdge {
        return $this->messageService->addMessage(
            roomGuid: (int)$roomGuid,
            user: $loggedInUser,
            message: $plainText
        );
    }

    #[Query]
    #[Logged]
    public function getChatRoomInviteRequests(
        #[InjectUser] User $loggedInUser,
        int $first = 12,
        ?int $after = null,
    ): ChatRoomsConnection {
        $connection = new ChatRoomsConnection();

        $chatRoomEdges = $this->roomService->getRoomInviteRequestsByMember($loggedInUser);

        $connection->setEdges($chatRoomEdges);

        $connection->setPageInfo(new PageInfo(
            hasNextPage: false,
            hasPreviousPage: false,
            startCursor: null,
            endCursor: null,
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

}
