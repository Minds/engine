<?php
/**
 * FeedCollection.
 *
 * @author edgebal
 */
namespace Minds\Core\Feeds;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Entities\User;

class FeedCollection
{
    /** @var User|null */
    protected $actor = null;

    /** @var string */
    protected $filter;

    /** @var string */
    protected $algorithm;

    /** @var string */
    protected $type;

    /** @var string */
    protected $period;

    /** @var int */
    protected $limit = 12;

    /** @var int */
    protected $offset = 0;

    /** @var int */
    protected $cap = 600;

    /** @var bool */
    protected $all = true;

    /** @var array|null */
    protected $hashtags = null;

    /**
     * @param User|null $actor
     * @return FeedCollection
     */
    public function setActor(?User $actor): FeedCollection
    {
        $this->actor = $actor;
        return $this;
    }

    /**
     * @param string $filter
     * @return FeedCollection
     */
    public function setFilter(string $filter): FeedCollection
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * @param string $algorithm
     * @return FeedCollection
     */
    public function setAlgorithm(string $algorithm): FeedCollection
    {
        $this->algorithm = $algorithm;
        return $this;
    }

    /**
     * @param string $type
     * @return FeedCollection
     */
    public function setType(string $type): FeedCollection
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param string $period
     * @return FeedCollection
     */
    public function setPeriod(string $period): FeedCollection
    {
        $this->period = $period;
        return $this;
    }

    /**
     * @param int $limit
     * @return FeedCollection
     */
    public function setLimit(int $limit): FeedCollection
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param int $offset
     * @return FeedCollection
     */
    public function setOffset(int $offset): FeedCollection
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @param int $cap
     * @return FeedCollection
     */
    public function setCap(int $cap): FeedCollection
    {
        $this->cap = $cap;
        return $this;
    }

    /**
     * @param bool $all
     * @return FeedCollection
     */
    public function setAll(bool $all): FeedCollection
    {
        $this->all = $all;
        return $this;
    }

    /**
     * @param array|null $hashtags
     * @return FeedCollection
     */
    public function setHashtags(?array $hashtags): FeedCollection
    {
        $this->hashtags = $hashtags;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function fetch()
    {
        if (!$this->filter) {
            throw new Exception('Missing filter');
        }

        if (!$this->algorithm /* TODO: Validate */) {
            throw new Exception('Missing algorithm');
        }

        if (!$this->type /* TODO: Validate */) {
            throw new Exception('Missing type');
        }

        if (!$this->period /* TODO: Validate */) {
            throw new Exception('Missing period');
        }

        $offset = abs(intval($this->offset ?: 0));
        $limit = abs(intval($this->limit ?: 0));

        if ($limit) {
            if ($this->cap && ($offset + $limit) > $this->cap) {
                $limit = $this->cap - $offset;
            }

            if ($limit < 0) {
                $emptyResponse = new Response([]);
                $emptyResponse
                    ->setPagingToken((string) $this->cap)
                    ->setLastPage(true)
                    ->setAttribute('overflow', true);

                return $emptyResponse;
            }
        }

        $all = !$this->hashtags && $this->all;

        $response = new Response();

        return $response;
    }
}
