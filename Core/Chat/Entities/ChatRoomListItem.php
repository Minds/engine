<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Entities;

use Minds\Common\Access;
use Minds\Entities\EntityInterface;

class ChatRoomListItem implements EntityInterface
{
    public function __construct(
        public readonly ChatRoom $chatRoom,
        public readonly ?string $lastMessagePlainText = null,
        public readonly ?int $lastMessageCreatedTimestamp = null,
        public readonly int $unreadMessagesCount = 0,
        /** @var int[] */
        public readonly array $memberGuids = [],
    ) {
    }

    public function getGuid(): ?string
    {
        return (string) $this->chatRoom->guid;
    }

    public function getOwnerGuid(): ?string
    {
        return (string) $this->chatRoom->createdByGuid;
    }

    public function getType(): ?string
    {
        return 'chat';
    }

    public function getSubtype(): ?string
    {
        return 'room-list-item';
    }

    public function getUrn(): string
    {
        return "urn:chat-list:chat:{$this->chatRoom->guid}";
    }

    public function getAccessId(): string
    {
        return (string) Access::UNLISTED;
    }
}
