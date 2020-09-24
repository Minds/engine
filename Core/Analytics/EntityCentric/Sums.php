<?php
/**
 * EntityCentric Sums
 * @author Mark
 */

namespace Minds\Core\Analytics\EntityCentric;

use DateTime;
use DateTimeZone;
use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Data\ElasticSearch\Client as ElasticClient;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Di\Di;

class Sums
{
    /** @var ElasticClient */
    protected $es;

    /**
     * Repository constructor.
     * @param ElasticClient $es
     */
    public function __construct(
        $es = null
    ) {
        $this->es = $es ?: Di::_()->get('Database\ElasticSearch');
    }

    public function getByOwner(array $opts = []): iterable
    {
        $opts = array_merge([
            'fields' => [],
            'from' => time(),
        ], $opts);

        $must = [];

        $must[] = [
            'range' => [
                '@timestamp' => [
                    'gte' => $opts['from'] * 1000,
                    'lt' => strtotime('+1 day', $opts['from']) * 1000,
                ],
            ],
        ];

        if ($opts['owner_guid']) {
            $must[] = [
                'term' => [
                    'owner_guid' => $opts['owner_guid']
                ]
            ];
        }


        $termsAgg = [];

        foreach ($opts['fields'] as $field) {
            $termsAgg[$field] = [
                'sum' => [
                    'field' => $field,
                ],
            ];
            $must[] = [
                'exists' => [
                    'field' => $field,
                ],
            ];
        }

        $partition = -1;
        $partitions = 100;
        $partitionSize = 5000; // Allows for 500,000 users

        if ($opts['owner_guid']) {
            $partitions = 1;
        }

        while (++$partition < $partitions) {
            // Do the query
            $query = [
                'index' => 'minds-entitycentric-*',
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
                                'field' => 'owner_guid',
                                'min_doc_count' =>  1,
                                'size' => $partitionSize,
                                'include' => [
                                    'partition' => $partition,
                                    'num_partitions' => $partitions,
                                ],
                            ],
                            'aggs' => $termsAgg,
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
