<?php

namespace Minds\Core\Chat\Entities;

use DateTime;
use DateTimeInterface;
use Minds\Common\Access;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Entities\EntityInterface;

class ChatRoom implements EntityInterface
{
    public readonly DateTimeInterface $createdAt;

    public function __construct(
        public readonly int              $guid,
        public readonly ChatRoomTypeEnum $roomType,
        public readonly int              $createdByGuid,
        ?DateTimeInterface               $createdAt = null,
        public readonly ?int             $groupGuid = null,
        public ?string                   $name = null,
    ) {
        $this->createdAt = $createdAt ?? new DateTime();
    }

    /**
     * Sets the name of the room
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getGuid(): ?string
    {
        return $this->guid;
    }

    /**
     * @inheritDoc
     */
    public function getUrn(): string
    {
        return "urn:chat:$this->guid";
    }

    /**
     * @inheritDoc
     */
    public function getType(): ?string
    {
        return 'chat';
    }

    /**
     * @inheritDoc
     */
    public function getSubtype(): ?string
    {
        return 'room';
    }

    /**
     * @inheritDoc
     */
    public function getOwnerGuid(): ?string
    {
        return (string)$this->createdByGuid;
    }

    /**
     * @inheritDoc
     */
    public function getAccessId(): string
    {
        return (int)Access::UNLISTED;
    }
}
