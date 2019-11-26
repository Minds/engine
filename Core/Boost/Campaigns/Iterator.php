<?php

namespace Minds\Core\Boost\Campaigns;

use Minds\Core\Di\Di;
use Minds\Traits\DiAlias;

class Iterator implements \Iterator
{
    use DiAlias;

    /** @var Manager */
    protected $manager;

    protected $limit = 12;
    protected $from = 0;
    protected $offset = null;
    protected $sort = 'asc';
    protected $type = 'newsfeed';
    protected $ownerGuid = null;
    protected $state = null;
    protected $rating = null;
    protected $quality = null;

    /** @var array */
    protected $list = null;

    public function __construct(Manager $manager = null)
    {
        $this->manager = $manager ?: Di::_()->get(Manager::getDiAlias());
    }

    /**
     * @param int $limit
     * @return Iterator
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param int $from
     * @return Iterator
     */
    public function setFrom(int $from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int|null $offset
     * @return Iterator
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @param string $sort
     * @return Iterator
     */
    public function setSort(string $sort)
    {
        $this->sort = $sort;
        return $this;
    }

    /**
     * @param mixed $type
     * @return Iterator
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param null $ownerGuid
     * @return Iterator
     */
    public function setOwnerGuid($ownerGuid)
    {
        $this->ownerGuid = $ownerGuid;
        return $this;
    }

    /**
     * @param mixed $state
     * @return Iterator
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @param int $rating
     * @return Iterator
     */
    public function setRating(int $rating)
    {
        $this->rating = $rating;
        return $this;
    }

    /**
     * @param int $quality
     * @return Iterator
     */
    public function setQuality(int $quality)
    {
        $this->quality = $quality;
        return $this;
    }

    public function getList()
    {
        $response = $this->manager->getCampaignsAndBoosts([
            'limit' => $this->limit,
            'from' => $this->from,
            'offset' => $this->offset,
            'type' => $this->type,
            'owner_guid' => $this->ownerGuid,
            'state' => $this->state,
            'rating' => $this->rating,
            'quality' => $this->quality,
        ]);

        $this->offset = $response->getPagingToken();
        $this->list = $response;
    }

    /**
     * @return Campaign
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
        if ($this->list) {
            reset($this->list);
        }
        $this->getList();
    }
}
