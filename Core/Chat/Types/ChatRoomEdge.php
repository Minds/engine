<?php
namespace Minds\Core\Chat\Types;

use Minds\Core\Chat\Controllers\ChatController;
use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class ChatRoomEdge implements EdgeInterface
{
    public function __construct(
        protected ChatRoomNode            $node,
        protected string                  $cursor = '',
        private readonly ?ChatController  $chatController = null,
        #[Field] #[Logged] public ?string $lastMessagePlainText = null,
        #[Field] #[Logged] public ?int    $lastMessageCreatedTimestamp = null,
    ) {
        
    }

    #[Field]
    public function getNode(): ChatRoomNode
    {
        return $this->node;
    }

    #[Field]
    public function getCursor(): string
    {
        return $this->cursor;
    }

    #[Field]
    #[Logged]
    public function getMessages(
        #[InjectUser] User $loggedInUser,
        ?int $first = null,
        ?int $after = null,
        ?int $last = null,
        ?int $before = null,
    ): ChatMessagesConnection {
        return $this->chatController->getChatMessages(
            roomGuid: $this->node->chatRoom->guid,
            loggedInUser: $loggedInUser,
            first: $first,
            after: $after,
            last: $last,
            before: $before
        );
    }

    #[Field]
    #[Logged]
    public function getTotalMembers(): int
    {
        return $this->chatController->getChatRoomMembersCount(
            roomGuid: $this->node->chatRoom->guid,
        );
    }

    #[Field]
    #[Logged]
    public function getMembers(
        ?int $first = null,
        ?int $after = null,
        ?int $last = null,
        ?int $before = null,
        #[InjectUser] User $loggedInUser,
    ): ChatRoomMembersConnection {
        return $this->chatController->getChatRoomMembers(
            roomGuid: $this->node->chatRoom->guid,
            first: $first,
            after: $after,
            last: $last,
            before: $before,
            loggedInUser: $loggedInUser
        );
    }


}
