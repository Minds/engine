<?php

namespace Minds\Core\Analytics\UserStates;

use Minds\Core\Time;

class ActiveUsersQueryBuilder
{
    protected $index = 'minds-metrics-*';
    protected $from = 0;
    protected $to = 0;
    protected $page = 0;
    protected $partitions = 200;
    protected $bucketSize = Time::ONE_DAY;

    public function __construct()
    {
        $this->from = strtotime('-7 days');
        $this->to = time();
    }

    /**
     * Set the current page to query
     * @param int $page
     * @return ActiveUsersQueryBuilder
     */
    public function setPage(int $page): self
    {
        $this->page = $page;
        return $this;
    }

    /**
     * Set the number of partitions
     * @param $partitions
     * @return ActiveUsersQueryBuilder
     */
    public function setPartitions($partitions): self
    {
        $this->partitions = $partitions;
        return $this;
    }

    /**
     * Set the from timestamp
     * @param int $from
     * @return ActiveUsersQueryBuilder
     */
    public function setFrom(int $from): self
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Set the to timestamp
     * @param int $to
     * @return ActiveUsersQueryBuilder
     */
    public function setTo(int $to): self
    {
        $this->to = $to;
        return $this;
    }

    /**
     * Set the bucket size (interval in seconds)
     * @param int $bucketSize
     * @return ActiveUsersQueryBuilder
     */
    public function setBucketSize(int $bucketSize): self
    {
        $this->bucketSize = $bucketSize;
        return $this;
    }

    /**
     * Return an ES query for the given parameters
     * @return array
     */
    public function query(): array
    {
        return [
            'index' => $this->index,
            'size' => '0',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $this->must(),
                    ],
                ],
                'aggs' => $this->aggregations(),
            ],
        ];
    }

    /**
     * Return a must clause for the query
     * @return array
     */
    private function must(): array
    {
        return [
            ['match_phrase' => [
                'action.keyword' => [
                    'query' => 'active',
                ],
            ]],
            ['range' => [
                '@timestamp' => [
                    'from' => $this->from * 1000,
                    'to' => $this->to * 1000,
                    'format' => 'epoch_millis',
                ],
            ]],
        ];
    }

    /**
     * Builds up an aggregate that splits a user's activity into buckets
     * @return array
     */
    private function aggregations(): array
    {
        return [
            'users' => [
                'terms' => [
                    'field' => 'user_guid.keyword',
                    'size' => 5000,
                    'include' => [
                        'partition' => $this->page,
                        'num_partitions' => $this->partitions,
                    ],
                ],
                'aggs' => $this->bucketAggregations(),
            ],
        ];
    }

    /**
     * Return the aggregations clauses for the ES query
     * @return array
     */
    private function bucketAggregations(): array
    {
        $bucketAggregations = [];

        foreach ($this->buckets() as $bucketTime) {
            $nextBucketTime = $bucketTime + $this->bucketSize;
            $bucketAggregations[$bucketTime] = $this->rangeAggregation($bucketTime, $nextBucketTime);
            $bucketAggregations["count-$bucketTime"] = $this->sumAggregation($bucketTime);
        }

        return $bucketAggregations;
    }

    /**
     * Return an array of bucket timestamps
     * @return array
     */
    public function buckets(): array
    {
        return Time::intervalsBetween($this->from, $this->to, $this->bucketSize);
    }

    /**
     * @param int $from
     * @param int $to
     * @return array
     */
    private function rangeAggregation(int $from, int $to): array
    {
        return [
            'date_range' => [
                'field' => '@timestamp',
                'ranges' => [
                    [
                        'from' => $from * 1000,
                        'to' => $to * 1000,
                    ],
                ],
            ],
        ];
    }

    /**
     * Builds up an aggregate that counts buckets with the same name
     * @param string $name
     * @return array
     */
    private function sumAggregation(string $name): array
    {
        return [
            'sum_bucket' => [
                'buckets_path' => "$name>_count",
            ],
        ];
    }
}
