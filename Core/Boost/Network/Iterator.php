<?php

namespace Minds\Core\Boost\Network;

use Minds\Core;
use Minds\Core\Di\Di;

class Iterator implements \Iterator
{
    const MAX_LIMIT = 50;
    const OFFSET_START = 0;

    /** @var ElasticRepository */
    protected $elasticRepository;
    /** @var Core\EntitiesBuilder */
    protected $entitiesBuilder;
    /** @var Manager */
    protected $manager;

    protected $rating = Boost::RATING_SAFE;
    protected $quality = 0;
    protected $offset = self::OFFSET_START;
    protected $limit = 10;
    protected $type = Boost::TYPE_NEWSFEED;
    // TODO: Mobile is using the hydrated entity response from `/api/v1/boost/fetch/newsfeed`?
    protected $hydrate = true;
    protected $blockedCount = 0;
    protected $userGuid = null;
    protected $fetchMore = true;

    protected $list = [];
    /** @var Boost[] $boosts */
    protected $boosts = [];

    public function __construct(
        $elasticRepository = null,
        $entitiesBuilder = null,
        $manager = null
    ) {
        $this->elasticRepository = $elasticRepository ?: new ElasticRepository;
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->manager = $manager ?: new Manager;
    }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function setQuality(int $quality): self
    {
        $this->quality = $quality;
        return $this;
    }

    public function setOffset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = min($limit, self::MAX_LIMIT);
        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setType(string $type): self
    {
        if (Boost::validType($type)) {
            $this->type = $type;
        }
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param bool $hydrate
     * @return Iterator
     */
    public function setHydrate(bool $hydrate): self
    {
        $this->hydrate = $hydrate;
        return $this;
    }

    public function setUserGuid(int $userGuid): self
    {
        $this->userGuid = $userGuid;
        return $this;
    }

    /**
     * Called by rewind() at the start of a foreach loop and attempts
     * to fetch $this->limit number of Boosts for iteration
     */
    protected function getList(): void
    {
        while ($this->fetchMore) {
            $boosts = $this->elasticRepository->getList([
                'type' => $this->type,
                'limit' => $this->limit,
                'offset' => $this->offset,
                'state' => Manager::OPT_STATEQUERY_APPROVED,
                'rating' => $this->rating,
            ]);

            $this->offset = intval($boosts->getPagingToken());
            $hasMore = $this->offset > 0;
            $boostsTaken = 0;

            foreach ($boosts as $boost) {
                $boostsTaken++;
                if ($this->isBlocked($boost)) {
                    $this->blockedCount++;
                    continue;
                }

                $this->offset = $boost->getCreatedTimestamp();

                /* If hydrate is set we return a list of entities *not boosts* */
                if ($this->hydrate) {
                    $boost = $this->manager->hydrate($boost);

                    if ($boost->hasEntity()) {
                        $this->list[$boost->getGuid()] = $boost->getEntity();
                    }
                } else {
                    $this->list[] = $boost;
                }

                if ($this->limitReached()) {
                    $this->fetchMore = false;
                    if (($boostsTaken === count($boosts)) && !$hasMore) {
                        $this->offset = self::OFFSET_START;
                    }
                    break;
                }

                if (!$hasMore) {
                    return;
                }
            }
        }
    }

    protected function isBlocked(Boost $boost): bool
    {
        if (is_null($this->userGuid)) {
            return false;
        } else {
            return Core\Security\ACL\Block::_()->isBlocked($boost->getOwnerGuid(), $this->userGuid);
        }
    }

    protected function limitReached(): bool
    {
        return count($this->list) === $this->limit;
    }

    public function blockedCount(): int
    {
        return $this->blockedCount;
    }

    /*
     * Iterator Methods
     */
    public function current()
    {
        return current($this->list);
    }

    public function next()
    {
        next($this->list);
    }

    public function key()
    {
        return key($this->list);
    }

    public function valid()
    {
        if (!$this->list) {
            return false;
        }
        return key($this->list) !== null;
    }

    public function rewind()
    {
        if (!empty($this->list)) {
            reset($this->list);
        } else {
            $this->getList();
        }
    }

    public function count(): int
    {
        return count($this->list);
    }
}
