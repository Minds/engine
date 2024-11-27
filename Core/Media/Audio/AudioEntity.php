<?php
namespace Minds\Core\Media\Audio;

use DateTimeImmutable;
use Minds\Common\Access;
use Minds\Entities\EntityInterface;

class AudioEntity implements EntityInterface
{
    public function __construct(
        public readonly int $guid,
        public readonly int $ownerGuid,
        public int $accessId = Access::UNLISTED,
        public float $durationSecs = 0,
        public ?string $remoteFileUrl = null,
        public ?DateTimeImmutable $uploadedAt = null,
        public ?DateTimeImmutable $processedAt = null,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getGuid(): ?string
    {
        return (string) $this->guid;
    }

    /**
     * @inheritDoc
     */
    public function getOwnerGuid(): ?string
    {
        return (string) $this->ownerGuid;
    }

    /**
     * @inheritDoc
     */
    public function getType(): ?string
    {
        return (string) 'audio';
    }

    /**
     * @inheritDoc
     */
    public function getSubtype(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getUrn(): string
    {
        return 'urn:audio:' . $this->guid;
    }

    /**
     * @inheritDoc
     */
    public function getAccessId(): string
    {
        return (string) $this->accessId;
    }
}
