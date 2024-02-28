<?php
namespace Minds\Core\Chat\Entities;

use DateTime;
use Minds\Entities\EntityInterface;

class ChatMessage implements EntityInterface
{
    public readonly DateTime $createdAt;

    public function __construct(
        public readonly int $roomGuid,
        public readonly int $guid,
        public readonly int $senderGuid,
        public readonly string $plainText,
        ?DateTime $createdAt = null
    ) {
        $this->createdAt = $createdAt ??= new DateTime();
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
        return "urn:chat:$this->roomGuid-$this->guid";
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
        return 'message';
    }

    /**
     * @inheritDoc
     */
    public function getOwnerGuid(): ?string
    {
        return (string) $this->senderGuid;
    }

    /**
     * @inheritDoc
     */
    public function getAccessId(): string
    {
        return (string) $this->roomGuid;
    }
}
