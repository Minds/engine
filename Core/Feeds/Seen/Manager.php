<?php

namespace Minds\Core\Feeds\Seen;

use Minds\Common\PseudonymousIdentifier;

class Manager
{
    public function __construct(
        private ?Repository $repository = null,
        private ?SeenCacheKeyCookie $seenCacheKeyCookie = null,
    ) {
        $this->repository ??= new Repository();
        $this->seenCacheKeyCookie = $this->seenCacheKeyCookie ?? new SeenCacheKeyCookie();
    }

    /**
     * Marks an array of entities as seen
     * @param string[] $entityGuids
     * @return void
     */
    public function seeEntities(array $entityGuids): void
    {
        foreach ($entityGuids as $entityGuid) {
            $seenEntity = new SeenEntity($this->getIdentifier(), $entityGuid, time());
            $this->repository->add($seenEntity);
        }
    }

    /**
     * Returns seen entities
     * @param int $limit
     * @return string[]
     */
    public function listSeenEntities(int $limit = 100): array
    {
        return array_map(function (SeenEntity $seenEntity) {
            return $seenEntity->getEntityGuid();
        }, [...$this->repository->getList(
            pseudoId: $this->getIdentifier(),
            limit: $limit,
        )]);
    }

    /**
     * If, for some reason, there is no pseudo id found, then we use
     * a generic cookie
     * @return SeenCacheKeyCookie
     */
    private function createSeenCacheKeyCookie(): SeenCacheKeyCookie
    {
        return $this->seenCacheKeyCookie->createCookie();
    }

    /**
     * Identifier. Will be pseudo if if found, if not we use a fallback cookie
     * @return string
     */
    public function getIdentifier(): string
    {
        $id = (new PseudonymousIdentifier())->getId();
        if (!$id) {
            $id = $this->createSeenCacheKeyCookie()->getValue();
        }
        return $id;
    }
}
