<?php
namespace Minds\Core\Chat\Types;

use DateTime;
use Minds\Core\Chat\Enums\ChatRoomRoleEnum;
use Minds\Core\Feeds\GraphQL\Types\UserNode;
use Minds\Core\GraphQL\Types\EdgeInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class ChatRoomMemberEdge implements EdgeInterface
{
    public function __construct(
        protected UserNode $node,
        protected string $cursor = ''
    ) {
        
    }

    #[Field]
    public function getNode(): UserNode
    {
        return $this->node;
    }

    #[Field]
    public function getCursor(): string
    {
        return $this->cursor;
    }

    /**
     * The role a member has in the room
     */
    #[Field]
    public function getRole(): ChatRoomRoleEnum
    {
        return ChatRoomRoleEnum::OWNER;
    }

    /**
     * The timestamp the message was sent at
     */
    #[Field]
    public function getTimeJoinedISO8601(): string
    {
        return (new DateTime())->format('c');
    }

    /**
     * The timestamp the message was sent at
     */
    #[Field]
    public function getTimeJoinedUnix(): string
    {
        return (new DateTime())->format('U');
    }
}
