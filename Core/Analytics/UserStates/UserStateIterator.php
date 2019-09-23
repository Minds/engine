<?php

namespace Minds\Core\Analytics\UserStates;

use Minds\Core;
use Minds\Core\Di\Di;

/*
* Iterator that loops through users and counts their action.active entries for the past N days
* All times adjusted to midnight to span the entire day
* Takes a reference day (eg today) and bucket sums user activity back N days
*/

class UserStateIterator implements \Iterator
{
    private $cursor = -1;
    private $partitions = 200;
    private $page = -1;
    private $data = [];
    private $valid = true;

    private $client;
    private $position;
    private $referenceTimestamp;
    private $intervalSize = Core\Time::ONE_DAY;

    public function __construct($client = null)
    {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');
        $this->position = 0;
        $this->referenceTimestamp = strtotime('midnight');
    }

    /**
     * Sets the last interval timestamp for the iterator
     * @param int $referenceTimestamp
     * @return self $this
     */
    public function setReferenceTimestamp(int $referenceTimestamp): self
    {
        $this->referenceTimestamp = $referenceTimestamp;
        return $this;
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

    public function get(): bool
    {
        if ($this->page++ >= $this->partitions - 1) {
            $this->valid = false;
            return false;
        }

        /* Round reference to interval and include previous interval for previous state */
        $to = Core\Time::toInterval($this->referenceTimestamp, $this->intervalSize);
        $from = $to - $this->intervalSize;

        $must = [
            [
                'range' => [
                    'reference_date' => [
                        'gte' => $from * 1000, //midnight of the first day
                        'lte' => $to * 1000, //midnight of the last day
                        'format' => 'epoch_millis',
                    ],
                ]
            ],
        ];

        //split up users by user guid
        $aggs = [
            'user_state' => [
                'terms' => [
                    'field' => 'user_guid',
                    'size' => 5000,
                    'include' => [
                        'partition' => $this->page,
                        'num_partitions' => $this->partitions,
                    ],
                ],
                'aggs' => [
                    'latest_state' => [
                        'top_hits' => [
                            'docvalue_fields' => ['state'],
                            'size' => 2,
                            'sort' => [
                                'reference_date' => [
                                    'order' => 'desc',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $query = [
            'index' => 'minds-kite',
            'size' => '2',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $must,
                    ],
                ],
                'aggs' => $aggs,
            ],
        ];

        $prepared = new Core\Data\ElasticSearch\Prepared\Search();
        $prepared->query($query);

        try {
            $result = $this->client->request($prepared);
        } catch (\Exception $e) {
            error_log($e);
            return false;
        }

        if ($result && $result['aggregations']['user_state']['buckets']) {
            if (isset($result['aggregations']['user_state']['buckets'][0]['latest_state']['hits']['hits'][0])) {
                $document = $result['aggregations']['user_state']['buckets'][0]['latest_state']['hits']['hits'][0];
                if (isset($result['aggregations']['user_state']['buckets'][0]['latest_state']['hits']['hits'][1])) {
                    $previousDocument = $result['aggregations']['user_state']['buckets'][0]['latest_state']['hits']['hits'][1];
                    $userState = (new UserState())
                        ->setUserGuid($document['_source']['user_guid'])
                        ->setReferenceDateMs($document['_source']['reference_date'])
                        ->setState($document['_source']['state'])
                        ->setPreviousState($previousDocument['_source']['state'])
                        ->setActivityPercentage($document['_source']['activity_percentage']);
                    $this->data[] = $userState;
                } else {
                    $userState = (new UserState())
                        ->setUserGuid($document['_source']['user_guid'])
                        ->setReferenceDateMs($document['_source']['reference_date'])
                        ->setState($document['_source']['state'])
                        ->setActivityPercentage($document['_source']['activity_percentage']);
                    $this->data[] = $userState;
                }
            }
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
     * @return UserState
     */
    public function current()
    {
        return $this->data[$this->cursor];
    }

    /**
     * Get cursor's key.
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
     * @return bool
     */
    public function valid(): bool
    {
        return $this->valid && isset($this->data[$this->cursor]);
    }
}
