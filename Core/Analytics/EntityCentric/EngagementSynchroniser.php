<?php
namespace Minds\Core\Analytics\EntityCentric;

use Minds\Core\Data\ElasticSearch;
use Minds\Core\Di\Di;
use DateTime;
use Exception;

class EngagementSynchroniser
{
    /** @var array */
    private $records = [];

    /** @var ElasticSearch\Client */
    private $es;

    /** @var int */
    protected $from;

    public function __construct($es = null)
    {
        $this->es = $es ?? Di::_()->get('Database\ElasticSearch');
    }

    /**
     * @param int $from
     * @return self
     */
    public function setFrom($from): self
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Convert to records
     * @return iterable
     */
    public function toRecords(): iterable
    {
        $date = (new DateTime())->setTimestamp($this->from);
        $now = new DateTime();
        $days = (int) $date->diff($now)->format('%a');
        $months = round($days / 28);

        $i = 0;
        foreach ($this->getEntitiesMetrics() as $buckets) {
            $urn = null;
            $ownerGuid = null;
            if (!$buckets['type']['buckets'][0]['key'] && $buckets['metrics']['buckets'][0]['key'] === 'subscribe') {
                $urn = "urn:user:{$buckets['key']}";
                $ownerGuid = (string) $buckets['key'];
            } elseif (!$buckets['type']['buckets'][0]['key']) {
                echo "\nEngagement: skipping as no type";
                continue;
            } else {
                $urn = "urn:{$buckets['type']['buckets'][0]['key']}:{$buckets['key']}";
                $ownerGuid = (string) $buckets['owner']['buckets'][0]['key'];
                if ($buckets['type']['buckets'][0]['key'] === 'object') {
                    $urn = "urn:{$buckets['subtype']['buckets'][0]['key']}:{$buckets['key']}";
                }
            }
            $record = new EntityCentricRecord();
            $record->setEntityUrn($urn)
                ->setOwnerGuid($ownerGuid)
                ->setTimestamp($this->from)
                ->setResolution('day');

            foreach ($buckets['metrics']['buckets'] as $metrics) {
                $aggType = 'total';
                if ($metrics['key'] === 'referral') {
                    $aggType = 'rewards';
                }
                $record->incrementSum("{$metrics['key']}::{$aggType}", (int) $metrics['doc_count']);
            }
            $this->records[] = $record;
            ++$i;
            error_log("Engagement: $i");
        }

        foreach ($this->records as $record) {
            yield $record;
        }
    }

    private function getEntitiesMetrics()
    {
        $opts = array_merge([
            'fields' => [],
            'from' => time(),
        ], []);

        $must = [];

        // $must[] = [
        //    'term' => [
        //        'action.keyword' => 'subscribe',
        //    ],
        //];

        $must[] = [
            'range' => [
                '@timestamp' => [
                    'gte' => $this->from * 1000,
                    'lt' => strtotime('+1 day', $this->from) * 1000,
                ],
            ],
        ];

        $partition = 0;
        $partitions = 50;
        $partitionSize = 5000; // Allows for 250,000 entities
        $index = 'minds-metrics-' . date('m-Y', $this->from);

        while (++$partition < $partitions) {
            // Do the query
            $query = [
                'index' => $index,
                'size' => 0,
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => $must,
                        ],
                    ],
                    'aggs' => [
                        '1' => [
                            'terms' => [
                                'field' => 'entity_guid.keyword',
                                'min_doc_count' =>  1,
                                'size' => $partitionSize,
                                'include' => [
                                    'partition' => $partition,
                                    'num_partitions' => $partitions,
                                ],
                            ],
                            'aggs' => [
                                'metrics' => [
                                    'terms' => [
                                        'field' => 'action.keyword',
                                        'min_doc_count' =>  1,
                                    ],
                                ],
                                'owner' => [
                                    'terms' => [
                                        'field' => 'entity_owner_guid.keyword',
                                        'min_doc_count' =>  1,
                                    ],
                                ],
                                'type' => [
                                    'terms' => [
                                        'field' => 'entity_type.keyword',
                                    ],
                                ],
                                'subtype' => [
                                    'terms' => [
                                        'field' => 'entity_subtype.keyword',
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            // Query elasticsearch
            $prepared = new ElasticSearch\Prepared\Search();
            $prepared->query($query);
            $response = $this->es->request($prepared);

            foreach ($response['aggregations']['1']['buckets'] as $bucket) {
                yield $bucket;
            }
        }
    }
}
