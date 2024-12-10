<?php
namespace Minds\Core\Chat\Entities;

use DateTime;
use DateTimeInterface;
use Minds\Core\Chat\Enums\ChatMessageTypeEnum;
use Minds\Core\Di\Di;
use Minds\Entities\EntityInterface;
use Minds\Helpers\Export;

class ChatMessage implements EntityInterface
{
    public const URN_METHOD = 'chatmessage';
    public readonly DateTimeInterface $createdAt;
    public readonly int $container_guid;

    public function __construct(
        public readonly int $roomGuid,
        public readonly int $guid,
        public readonly int $senderGuid,
        public readonly string $plainText,
        public readonly ChatMessageTypeEnum $messageType = ChatMessageTypeEnum::TEXT,
        public readonly ?ChatRichEmbed $richEmbed = null,
        public readonly ?ChatImage $image = null,
        ?DateTimeInterface $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTime();
        $this->container_guid = $this->roomGuid;
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
        return "urn:" . self::URN_METHOD . ":{$this->roomGuid}_$this->guid";
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

    public function getNsfw(): array
    {
        return [];
    }

    public function export(): array
    {
        $sender = Di::_()->get('EntitiesBuilder')->single($this->senderGuid);
        return [
            'guid' => $this->guid,
            'roomGuid' => $this->roomGuid,
            'type' => $this->getType(),
            'subtype' => $this->getSubtype(),
            'sender' => $sender?->export(),
            'plainText' => Export::sanitizeString($this->plainText),
            'createdTimestampUnix' => $this->createdAt->getTimestamp(),
            'richEmbed' => $this->richEmbed?->export(),
            'image' => $this->image?->export(),
        ];
    }
}
