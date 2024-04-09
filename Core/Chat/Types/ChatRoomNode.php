<?php

namespace Minds\Core\Chat\Types;

use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\GraphQL\Types\NodeInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

#[Type]
class ChatRoomNode implements NodeInterface
{
    public function __construct(
        public readonly ChatRoom $chatRoom,
        #[Field] #[Logged] public bool $isChatRequest = false,
        #[Field] #[Logged] public ?bool $isUserRoomOwner = null,
        #[Field] #[Logged] public ?bool $areChatRoomNotificationsMuted = false
    ) {

    }

    #[Field]
    public function getId(): ID
    {
        return new ID($this->chatRoom->getUrn());
    }

    /**
     * The unique guid of the room
     */
    #[Field]
    public function getGuid(): string
    {
        return (string)$this->chatRoom->guid;
    }

    /**
     * The type of room. i.e. one-to-one, multi-user, or group-owned
     */
    #[Field]
    public function getRoomType(): ChatRoomTypeEnum
    {
        return $this->chatRoom->roomType;
    }

    /**
     * The timestamp the room was created at
     */
    #[Field]
    public function getTimeCreatedISO8601(): string
    {
        return $this->chatRoom->createdAt->format('c');
    }

    /**
     * The timestamp the roomt was created at
     */
    #[Field]
    public function getTimeCreatedUnix(): string
    {
        return $this->chatRoom->createdAt->format('U');
    }
}
