<?php
/**
 * Leaderboard for offchain transfers (admin console)
 */
namespace Minds\Core\Wire;

use Minds\Core\Di\Di;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Data\ElasticSearch\Client;

class Leaderboard
{
    /** @var Client */
    protected $es;

    public function __construct($es = null)
    {
        $this->es = $es ?? Di::_()->get('Database\ElasticSearch');
    }

    /**
     * @param $from timestamp
     * @param $to timestamp
     * @param $field either 'wire_sender_guid' or 'wire_receiver_guid'
     * @return array
     */
    public function fetchOffchain($from, $to, $field): array
    {
        $query = [
            'index' =>  'minds-offchain*',
            'size' => 0,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'range' => [
                                    'amount' => [
                                        'gt' => 0
                                    ]
                                ]
                            ],
                            [
                                'range' => [
                                    '@timestamp' => [
                                        'gte' => $from,
                                        'lte' => $to,
                                        'format' => 'epoch_millis'
                                    ],

                                ]
                            ]
                        ],
                    ],
                ],
                'aggs' => [
                    '1' => [
                        'terms' => [
                            'field' => $field,
                            'size' => 50,
                            'order' => [
                                '2' => 'desc',
                            ],
                        ],
                        'aggs' => [
                            '2' => [
                                'sum' => [
                                    'field' => 'amount',
                                ],
                            ]
                        ],
                    ],

                ],
            ],
        ];

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);
        $result = $this->es->request($prepared);

        $leaders = [];

        foreach ($result['aggregations']['1']['buckets'] as $bucket) {
            $userGuid = $bucket['key'];

            $leader = [
                'user_guid'=> $userGuid,
                'value'=> $bucket['2']['value'],
                'count'=>$bucket['doc_count']
            ];

            array_push($leaders, $leader);
        }

        return $leaders;
    }
}
