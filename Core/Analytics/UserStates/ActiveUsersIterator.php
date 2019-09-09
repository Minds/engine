<?php

namespace Minds\Core\Analytics\UserStates;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Data;

/*
* Iterator that loops through users and counts their action.active entries for the past N days
* All times adjusted to midnight to span the entire day
* Takes a reference day (eg today) and bucket sums user activity back N days
*/

class ActiveUsersIterator implements \Iterator
{
    /** @var UserActivityBuckets[] $data */
    protected $data = [];
    protected $valid = true;
    protected $referenceDate;
    protected $rangeOffset = 7;
    /** @var Data\ElasticSearch\Client */
    protected $client;
    /** @var ActiveUsersQueryBuilder */
    protected $queryBuilder;
    protected $cursor = -1;
    protected $partitions = 200;
    protected $page = -1;

    public function __construct(Data\ElasticSearch\Client $client = null, ActiveUsersQueryBuilder $queryBuilder = null)
    {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');
        $this->queryBuilder = $queryBuilder ?? new ActiveUsersQueryBuilder();
        $this->queryBuilder->setPartitions($this->partitions);
        $this->referenceDate = strtotime('midnight');
    }

    /**
     * Sets the last day for the iterator (ie, today)
     * @param int $referenceDate
     * @return ActiveUsersIterator
     */
    public function setReferenceDate(int $referenceDate): self
    {
        $this->referenceDate = $referenceDate;
        return $this;
    }

    /**
     * Sets the number of days to look backwards
     * @param int $rangeOffset
     * @return ActiveUsersIterator
     */
    public function setRangeOffset(int $rangeOffset): self
    {
        $this->rangeOffset = $rangeOffset;
        return $this;
    }

    public function get(): bool
    {
        if ($this->page++ >= $this->partitions - 1) {
            $this->valid = false;
            return false;
        }

        $from = strtotime("-$this->rangeOffset day", $this->referenceDate);
        $query = $this->queryBuilder->setFrom($from)->setTo($this->referenceDate)->setPage($this->page)->query();

        $prepared = new Core\Data\ElasticSearch\Prepared\Search();
        $prepared->query($query);

        try {
            $result = $this->client->request($prepared);
        } catch (\Exception $e) {
            error_log($e);
            return false;
        }

        if (!$result || $result['hits']['total'] == 0) {
            return false;
        }

        /* Derive activity data from the ES results */
        foreach ($result['aggregations']['users']['buckets'] as $userActivityByDay) {
            $userActivityBuckets = (new UserActivityBuckets())
                ->setUserGuid($userActivityByDay['key'])
                ->setReferenceDateMs($this->referenceDate * 1000);

            $days = [];
            foreach ($this->queryBuilder->buckets() as $bucketTime) {
                $days[] = [
                    'reference_date' => $userActivityByDay[$bucketTime]['buckets'][0]['from'],
                    'count' => $userActivityByDay["count-$bucketTime"]['value'],
                ];
            }

            $userActivityBuckets->setActiveDaysBuckets($days);
            $this->data[] = $userActivityBuckets;
        }
        if ($this->cursor >= count($this->data)) {
            $this->get();
        }

        return true;
    }

    /**
     * Rewind the array cursor.
     */
    public function rewind()
    {
        if ($this->cursor >= 0) {
            $this->get();
        }
        $this->next();
    }

    /**
     * Get the current cursor's data.
     *
     * @return UserActivityBuckets
     */
    public function current()
    {
        return $this->data[$this->cursor];
    }

    /**
     * Get cursor's key.
     *
     * @return mixed
     */
    public function key()
    {
        return $this->cursor;
    }

    /**
     * Goes to the next cursor.
     */
    public function next()
    {
        ++$this->cursor;
        if (!isset($this->data[$this->cursor])) {
            $this->get();
        }
    }

    /**
     * Checks if the cursor is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->valid && isset($this->data[$this->cursor]);
    }
}
