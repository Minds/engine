<?php
namespace Minds\Core\Chat\Controllers;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Chat\Types\ChatMessageEdge;
use Minds\Core\Chat\Types\ChatMessageNode;
use Minds\Core\Chat\Types\ChatMessagesConnection;
use Minds\Core\Chat\Types\ChatRoomEdge;
use Minds\Core\Chat\Types\ChatRoomMemberEdge;
use Minds\Core\Chat\Types\ChatRoomMemberNode;
use Minds\Core\Chat\Types\ChatRoomMembersConnection;
use Minds\Core\Chat\Types\ChatRoomNode;
use Minds\Core\Chat\Types\ChatRoomsConnection;
use Minds\Core\Feeds\GraphQL\Types\UserNode;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\Guid;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class ChatController
{
    public function __construct(
        private readonly RoomService $roomService
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

        $connection->setEdges([
            new ChatRoomEdge(
                node: new ChatRoomNode(
                    new ChatRoom(
                        guid: Guid::build(),
                        roomType: ChatRoomTypeEnum::ONE_TO_ONE,
                        createdByGuid: (int) $loggedInUser->getGuid(),
                    )
                ),
                chatController: $this,
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
     * Returns a chat room
     */
    #[Query]
    #[Logged]
    public function getChatRoom(
        int $roomGuid,
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
        int $roomGuid,
        ?int $first = null,
        ?int $after = null,
        ?int $last = null,
        ?int $before = null,
        #[InjectUser] User $loggedInUser,
    ): ChatMessagesConnection {
        $connection = new ChatMessagesConnection();

        $connection->setEdges([
            new ChatMessageEdge(
                node: new ChatMessageNode(
                    new ChatMessage(
                        roomGuid: $roomGuid,
                        guid: Guid::build(),
                        senderGuid: (int) $loggedInUser->getGuid(),
                        plainText: 'Hello world',
                    )
                )
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
        ?int $roomGuid = null,
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
     * @param string[] $initialMemberGuids
     */
    #[Mutation]
    #[Logged]
    public function createChatRoom(
        #[InjectUser] User $loggedInUser,
        array $initialMemberGuids = [],
    ): ChatRoomEdge {
        $chatRoomNode = $this->roomService->createRoom(
            user: $loggedInUser,
            members: $initialMemberGuids
        );
        return new ChatRoomEdge(
            node: $chatRoomNode,
            chatController: $this,
        );
    }

    /**
     * Creates a new message in a chat room
     */
    #[Mutation]
    #[Logged]
    public function createChatMessage(
        string $plainText,
        int $roomGuid,
        #[InjectUser] User $loggedInUser,
    ): ChatMessageEdge {
        return new ChatMessageEdge(
            new ChatMessageNode(
                new ChatMessage(
                    roomGuid: $roomGuid,
                    guid: Guid::build(),
                    senderGuid: (int) $loggedInUser->getGuid(),
                    plainText: $plainText,
                )
            )
        );
    }

}
