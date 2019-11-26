<?php

namespace Minds\Core\Boost;

use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Entities\Resolver;
use Minds\Entities\User;
use Minds\Core;

abstract class Feed
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
    protected $offset;
    protected $limit;
    protected $rating;
    protected $platform;
    protected $quality = 0;
    protected $type = 'newsfeed';

    protected $offsetCacheTtl = Core\Time::FIVE_MIN;

    public function __construct(
        User $currentUser = null,
        Resolver $resolver = null,
        abstractCacher $cacher = null
    ) {
        $this->currentUser = $currentUser ?: Core\Session::getLoggedinUser();
        $this->resolver = $resolver ?: new Resolver();
        $this->cacher = $cacher ?: Core\Data\cache\factory::build('Redis');
    }

    public function setMockIterator($mockIterator): self
    {
        $this->mockIterator = $mockIterator;
        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function setPlatform(string $platform): self
    {
        $this->platform = $platform;
        return $this;
    }

    public function setQuality(int $quality): self
    {
        $this->quality = $quality;
        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
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

    protected function makeRatingAndQualitySafe(): void
    {
        if ($this->platform === 'ios') {
            $this->rating = Core\Boost\Network\Boost::RATING_SAFE;
            $this->quality = 90;
        } elseif (time() - $this->currentUser->getTimeCreated() <= Core\Time::ONE_HOUR) {
            $this->rating = Core\Boost\Network\Boost::RATING_SAFE;
            $this->quality = 75;
        }
    }

    protected function setOffsetFromCache(): void
    {
        $offsetCache = $this->getOffsetCache();
        if (is_int($offsetCache)) {
            $this->offset = $offsetCache;
        }
    }

    protected function setOffsetCache(): void
    {
        $this->cacher->set($this->getOffsetCacheKey(), $this->offset, $this->offsetCacheTtl);
    }

    protected function getOffsetCache()
    {
        return $this->cacher->get($this->getOffsetCacheKey());
    }

    abstract protected function getItems(): void;

    abstract protected function getOffsetCacheKey(): string;
}
