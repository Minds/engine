<?php
namespace Minds\Core\Chat\Types;

use Minds\Core\Chat\Controllers\ChatController;
use Minds\Core\Di\Di;
use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

#[Type]
class ChatRoomEdge implements EdgeInterface
{
    private ?ChatController $chatController;

    public function __construct(
        protected ChatRoomNode            $node,
        protected string                  $cursor = '',
        ?ChatController  $chatController = null,
        #[Field] #[Logged] public ?string $lastMessagePlainText = null,
        #[Field] #[Logged] public ?int    $lastMessageCreatedTimestamp = null,
        #[Field] #[Logged] public int     $unreadMessagesCount = 0,
    ) {
        $this->chatController = $chatController;
    }

    #[Field]
    public function getId(): ID
    {
        return $this->node->getId();
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
        ?string $after = null,
        ?string $before = null,
    ): ChatMessagesConnection {
        $this->initialiseChatControllerInstance();
        return $this->chatController->getChatMessages(
            roomGuid: $this->node->chatRoom->guid,
            loggedInUser: $loggedInUser,
            first: $first,
            after: $after,
            before: $before
        );
    }

    #[Field]
    #[Logged]
    public function getTotalMembers(): int
    {
        $this->initialiseChatControllerInstance();
        return $this->chatController->getChatRoomMembersCount(
            roomGuid: $this->node->chatRoom->guid,
        );
    }

    #[Field]
    #[Logged]
    public function getMembers(
        #[InjectUser] User $loggedInUser,
        ?int $first = null,
        ?int $after = null,
        ?int $last = null,
        ?int $before = null,
    ): ChatRoomMembersConnection {
        $this->initialiseChatControllerInstance();
        return $this->chatController->getChatRoomMembers(
            loggedInUser: $loggedInUser,
            roomGuid: $this->node->chatRoom->guid,
            first: $first,
            after: $after,
            last: $last,
            before: $before
        );
    }

    private function initialiseChatControllerInstance(): void
    {
        $this->chatController ??= Di::_()->get(ChatController::class);
    }
}
