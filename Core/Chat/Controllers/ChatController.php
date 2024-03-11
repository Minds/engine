<?php
namespace Minds\Core\Chat\Controllers;

use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Exceptions\InvalidChatRoomTypeException;
use Minds\Core\Chat\Services\MessageService;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Chat\Types\ChatMessageEdge;
use Minds\Core\Chat\Types\ChatMessagesConnection;
use Minds\Core\Chat\Types\ChatRoomEdge;
use Minds\Core\Chat\Types\ChatRoomMemberEdge;
use Minds\Core\Chat\Types\ChatRoomMemberNode;
use Minds\Core\Chat\Types\ChatRoomMembersConnection;
use Minds\Core\Chat\Types\ChatRoomNode;
use Minds\Core\Chat\Types\ChatRoomsConnection;
use Minds\Core\Feeds\GraphQL\Types\UserNode;
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
        ?int $first = null,
        ?int $after = null,
        ?int $last = null,
        ?int $before = null,
        #[InjectUser] ?User $loggedInUser = null,
    ): ChatRoomsConnection {
        $connection = new ChatRoomsConnection();

        $chatRoomEdges = $this->roomService->getRoomsByMember($loggedInUser);

        $connection->setEdges($chatRoomEdges);

        $connection->setPageInfo(new PageInfo(
            hasNextPage: false,
            hasPreviousPage: false,
            startCursor: null,
            endCursor: null,
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
        return new ChatRoomEdge(
            node: new ChatRoomNode(
                new ChatRoom(
                    guid: $roomGuid,
                    roomType: ChatRoomTypeEnum::ONE_TO_ONE,
                    createdByGuid: (int) $loggedInUser->getGuid(),
                )
            ),
            chatController: $this,
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

        $connection->setEdges([
            $this->messageService->getMessages(
                roomGuid: (int)$roomGuid,
            )
        ]);

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
    #[Query()]
    #[Logged]
    public function getChatRoomMembers(
        ?string $roomGuid = null,
        ?int $first = null,
        ?int $after = null,
        ?int $last = null,
        ?int $before = null,
        #[InjectUser] User $loggedInUser,
    ): ChatRoomMembersConnection {
        $connection = new ChatRoomMembersConnection();

        $connection->setEdges([
            new ChatRoomMemberEdge(
                node: new UserNode($loggedInUser, $loggedInUser),
            )
        ]);

        $connection->setPageInfo(new PageInfo(
            hasNextPage: false,
            hasPreviousPage: false,
            startCursor: null,
            endCursor: null,
        ));

        return $connection;
    }

    /**
     * Creates a new chat room
     * @param array $otherMemberGuids
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
        $chatRoomNode = $this->roomService->createRoom(
            user: $loggedInUser,
            otherMemberGuids: $otherMemberGuids,
            roomType: $roomType
        );

        return new ChatRoomEdge(
            node: $chatRoomNode,
            chatController: $this,
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
        $chatMessageNode = $this->messageService->addMessage(
            roomGuid: (int)$roomGuid,
            user: $loggedInUser,
            message: $plainText
        );
        return new ChatMessageEdge(
            node: $chatMessageNode,
        );
    }

}
