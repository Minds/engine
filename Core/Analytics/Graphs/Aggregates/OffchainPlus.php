<?php

namespace Minds\Core\Analytics\Graphs\Aggregates;

use DateTime;
use Minds\Core\Analytics\Graphs\Manager;
use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Di\Di;

class OffchainPlus implements AggregateInterface
{
    /** @var Client */
    protected $client;
    /** @var abstractCacher */
    protected $cacher;
    /** @var string */
    protected $index;
    /** @var string */
    protected $dateFormat;

    public function __construct($client = null, $cacher = null)
    {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');
        $this->cacher = $cacher ?: Di::_()->get('Cache\Redis');
        $this->index = 'minds-offchain*';
    }

    public function hasTTL(array $opts = [])
    {
        return false;
    }

    public function buildCacheKey(array $options = [])
    {
        return "offchain:plus:{$options['key']}:{$options['unit']}";
    }

    /**
     * Fetch all
     * @param array $opts
     * @return array
     */
    public function fetchAll($opts = [])
    {
        $result = [];
        $span = null;
        foreach (['month'] as $unit) {
            $k = Manager::buildKey([
                'aggregate' => $opts['aggregate'] ?? 'offchainplus',
                'key' => null,
                'unit' => $unit,
                'span' => 13,
            ]);
            $result[$k] = $this->fetch([
                'key' => null,
                'unit' => $unit,
                'span' => 13,
            ]);

            $avgKey = Manager::buildKey([
                'aggregate' => $opts['aggregate'] ?? 'offchainplus',
                'key' => 'avg',
                'unit' => $unit,
                'span' => 13,
            ]);
            $result[$avgKey] = Manager::calculateAverages($result[$k]);
        }
        return $result;
    }

    public function fetch(array $options = [])
    {
        $options = array_merge([
            'span' => 13,
            'unit' => 'month', // day / month
            'key' => null,
        ], $options);

        $from = null;
        switch ($options['unit']) {
            case "month":
                $to = new DateTime('midnight first day of next month');
                $from = (new DateTime('midnight'))
                    ->modify("-{$options['span']} months");

                $interval = '1M';
                $this->dateFormat = 'y-m';
                break;
            default:
                throw new \Exception("{$options['unit']} is not an accepted unit");
        }

        return $this->getGraph($from, $to, $interval);
    }

    private function getGraph($from, $to, $interval)
    {
        $must = [
            [
                "match_all" => (object) []
            ],
            [
                "range" => [
                    "@timestamp" => [
                        "gte" => $from->getTimestamp() * 1000,
                        "lte" => $to->getTimestamp() * 1000,
                        "format" => "epoch_millis"
                    ]
                ]
            ],
            [
                "match_phrase" => [
                    "amount" => [
                        "query" => -20
                    ]
                ]
            ],
            [
                "match" => [
                    "wire_receiver_guid" => "730071191229833224"
                ]
            ]
        ];

        $query = [
            'index' => $this->index,
            'size' => 0,
            "stored_fields" => [
                "*"
            ],

            "docvalue_fields" => [
                (object) [
                    "field" => "@timestamp",
                    "format" => "date_time"
                ]
            ],
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $must,
                    ]
                ],
                "aggs" => [
                    "histogram" => [
                        "date_histogram" => [
                            "field" => "@timestamp",
                            "interval" => $interval,
                            "min_doc_count" => 1
                        ],
                        "aggs" => [
                            "amount" => [
                                "sum" => [
                                    "field" => "amount",
                                    "script" => [
                                        "lang" => "expression",
                                        "inline" => "doc['amount'] * multiplier",
                                        "params" => [
                                            "multiplier" => -1
                                        ]
                                    ]
                                ]
                            ],
                            "users" => [
                                "cardinality" => [
                                    "field" => "wire_sender_guid"
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $result = $this->client->request($prepared);

        $response = [
            [
                'key' => 'tokens',
                'name' => 'OffChain Plus Tokens',
                'x' => [],
                'y' => [],
            ],
            [
                'key' => 'users',
                'name' => 'OffChain Plus Users',
                'x' => [],
                'y' => [],
            ],
            [
                'key' => 'transactions',
                'name' => 'OffChain Plus Transactions',
                'x' => [],
                'y' => [],
            ]
        ];

        foreach ($result['aggregations']['histogram']['buckets'] as $count) {
            $date = date($this->dateFormat, $count['key'] / 1000);
            $response[0]['x'][] = $date;
            $response[0]['y'][] = $count['amount']['value'] ?? 0;

            $response[1]['x'][] = $date;
            $response[1]['y'][] = $count['users']['value'] ?? 0;

            $response[2]['x'][] = $date;
            $response[2]['y'][] = $count['doc_count'] ?? 0;
        }

        return $response;
    }
}
