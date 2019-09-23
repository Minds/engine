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
    protected $referenceTimestamp;
    protected $numberOfIntervals = 7;
    /** @var Data\ElasticSearch\Client */
    protected $client;
    /** @var ActiveUsersQueryBuilder */
    protected $queryBuilder;
    protected $cursor = -1;
    protected $partitions = 1;
    protected $page = -1;
    protected $intervalSize = Core\Time::ONE_DAY;

    public function __construct(Data\ElasticSearch\Client $client = null, ActiveUsersQueryBuilder $queryBuilder = null)
    {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');
        $this->queryBuilder = $queryBuilder ?? new ActiveUsersQueryBuilder();
        $this->queryBuilder->setPartitions($this->partitions);
        $this->referenceTimestamp = strtotime('midnight');
    }

    /**
     * Sets the interval/bucket size
     * @param int $intervalSize
     * @return self $this
     */
    public function setIntervalSize(int $intervalSize): self
    {
        $this->intervalSize = $intervalSize;
        return $this;
    }

    /**
     * Sets the reference for the last interval
     * @param int $referenceTimestamp
     * @return ActiveUsersIterator
     */
    public function setReferenceTimestamp(int $referenceTimestamp): self
    {
        $this->referenceTimestamp = Core\Time::toInterval($referenceTimestamp, $this->intervalSize);
        return $this;
    }

    /**
     * Sets the number of intervals to look backwards
     * @param int $numberOfIntervals
     * @return ActiveUsersIterator
     */
    public function setNumberOfIntervals(int $numberOfIntervals): self
    {
        $this->numberOfIntervals = $numberOfIntervals;
        return $this;
    }

    public function get(): bool
    {
        if ($this->page++ >= $this->partitions - 1) {
            $this->valid = false;
            return false;
        }

        $from = $this->referenceTimestamp - ($this->intervalSize * $this->numberOfIntervals);
        $to = $this->referenceTimestamp + ($this->intervalSize - 1); // Last timestamp of reference interval
        $query = $this->queryBuilder
            ->setFrom($from)
            ->setTo($to)
            ->setPage($this->page)
            ->query();

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
            $days = [];
            foreach ($this->queryBuilder->buckets() as $bucketTime) {
                $days[] = [
                    'reference_date' => $userActivityByDay[$bucketTime]['buckets'][0]['from'],
                    'count' => $userActivityByDay["count-$bucketTime"]['value'],
                ];
            }

            $userActivityBuckets = (new UserActivityBuckets())
                ->setUserGuid($userActivityByDay['key'])
                ->setReferenceDateMs($this->referenceTimestamp * 1000)
                ->setActiveDaysBuckets($days);

            usort($days, function ($a, $b) {
                return $a['reference_date'] <=> $b['reference_date'];
            });

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
