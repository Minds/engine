<?php

namespace Minds\Core\Boost;

use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Entities\Resolver;
use Minds\Entities\User;
use Minds\Core;
use Minds\Helpers\Time;

/**
 * Class AbstractFeed
 * @package Minds\Core\Boost
 */
abstract class AbstractFeed
{
    /** @var Resolver */
    protected $resolver;
    /** @var User */
    protected $currentUser;
    /** @var abstractCacher */
    protected $cacher;

    protected $mockIterator;

    /** @var array */
    protected $boosts = [];
    /** @var int */
    protected $offset;
    /** @var int */
    protected $limit;
    /** @var int */
    protected $rating;
    /** @var string */
    protected $platform;
    /** @var int */
    protected $quality = 0;
    /** @var string */
    protected $type = 'newsfeed';
    /** @var int */
    protected $offsetCacheTtl = Time::FIVE_MIN;

    /**
     * Feed constructor.
     * @param User|null $currentUser
     * @param Resolver|null $resolver
     * @param abstractCacher|null $cacher
     */
    public function __construct(
        User $currentUser = null,
        Resolver $resolver = null,
        abstractCacher $cacher = null
    ) {
        $this->currentUser = $currentUser ?: Core\Session::getLoggedinUser();
        $this->resolver = $resolver ?: new Resolver();
        $this->cacher = $cacher ?: Core\Data\cache\factory::build('Redis');
    }

    /**
     * Set a mock iterator
     * @param $mockIterator
     * @return $this
     */
    public function setMockIterator($mockIterator): self
    {
        $this->mockIterator = $mockIterator;
        return $this;
    }

    /**
     * Set limit
     * @param int $limit
     * @return $this
     */
    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Get offset
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Set offset
     * @param int $offset
     * @return $this
     */
    public function setOffset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Set rating
     * @param int $rating
     * @return $this
     */
    public function setRating(int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    /**
     * Set platform
     * @param string $platform
     * @return $this
     */
    public function setPlatform(string $platform): self
    {
        $this->platform = $platform;
        return $this;
    }

    /**
     * Set quality
     * @param int $quality
     * @return $this
     */
    public function setQuality(int $quality): self
    {
        $this->quality = $quality;
        return $this;
    }

    /**
     * Set type
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get the feed items
     * @return Core\Feeds\FeedSyncEntity[]
     */
    public function get(): array
    {
        $this->makeRatingAndQualitySafe();
        $this->setOffsetFromCache();
        $this->getItems();
        $this->setOffsetCache();
        return $this->boosts;
    }

    /**
     * Make rating and quality safe
     */
    protected function makeRatingAndQualitySafe(): void
    {
        if ($this->platform === 'ios') {
            $this->rating = Core\Boost\Network\Boost::RATING_SAFE;
            $this->quality = 90;
        } elseif (time() - $this->currentUser->getTimeCreated() <= Time::ONE_HOUR) {
            $this->rating = Core\Boost\Network\Boost::RATING_SAFE;
            $this->quality = 75;
        }
    }

    /**
     * Set the offset from cache
     */
    protected function setOffsetFromCache(): void
    {
        $offsetCache = $this->getOffsetCache();
        if (is_int($offsetCache)) {
            $this->offset = $offsetCache;
        }
    }

    /**
     * Set the offset cache
     */
    protected function setOffsetCache(): void
    {
        $this->cacher->set($this->getOffsetCacheKey(), $this->offset, $this->offsetCacheTtl);
    }

    /**
     * Get the offset cache
     * @return mixed
     */
    protected function getOffsetCache()
    {
        return $this->cacher->get($this->getOffsetCacheKey());
    }

    /**
     * Get items
     */
    abstract protected function getItems(): void;

    /**
     * Get offset cache key
     * @return string
     */
    abstract protected function getOffsetCacheKey(): string;
}
