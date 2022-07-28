<?php
namespace Minds\Core\Feeds\Seen;

class SeenEntity
{
    public function __construct(
        private string $pseudoId,
        private string $entityGuid,
        private int $lastSeenTimestamp,
    ) {
    }

    /**
     * @return string
     */
    public function getPseudoId(): string
    {
        return $this->pseudoId;
    }

    /**
     * @return string
     */
    public function getEntityGuid(): string
    {
        return $this->entityGuid;
    }

    /**
     * @return int
     */
    public function getLastSeenTimestamp(): int
    {
        return $this->lastSeenTimestamp;
    }
}
