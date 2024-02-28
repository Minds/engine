<?php
namespace Minds\Core\Chat\Types;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Feeds\GraphQL\Types\UserEdge;
use Minds\Core\GraphQL\Types\NodeInterface;
use Minds\Core\Session;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

#[Type]
class ChatMessageNode implements NodeInterface
{
    public function __construct(
        public readonly ChatMessage $chatMessage,
    ) {
        
    }

    #[Field]
    public function getId(): ID
    {
        return new ID($this->chatMessage->getUrn());
    }

    /**
     * The unique guid of the message
     */
    #[Field]
    public function getGuid(): string
    {
        return $this->chatMessage->guid;
    }
    
    /**
     * The guid of the room the message belongs to
     */
    #[Field]
    public function getRoomGuid(): string
    {
        return $this->chatMessage->roomGuid;
    }

    /**
     * The plaintext (non-encrypted) message
     */
    #[Field]
    public function getPlainText(): string
    {
        return $this->chatMessage->plainText;
    }

    /**
     * The user who sent the message
     */
    #[Field]
    public function getSender(): UserEdge
    {
        return new UserEdge(Session::getLoggedInUser(), '');
    }

    /**
     * The timestamp the message was sent at
     */
    #[Field]
    public function getTimeCreatedISO8601(): string
    {
        return $this->chatMessage->createdAt->format('c');
    }

    /**
     * The timestamp the message was sent at
     */
    #[Field]
    public function getTimeCreatedUnix(): string
    {
        return $this->chatMessage->createdAt->format('U');
    }

}
