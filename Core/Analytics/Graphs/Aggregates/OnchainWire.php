<?php

namespace Minds\Core\Analytics\Graphs\Aggregates;

use DateTime;
use Minds\Core\Analytics\Graphs\Manager;
use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Di\Di;

class OnchainWire implements AggregateInterface
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
        $this->index = 'minds-transactions-onchain*';
    }

    public function hasTTL(array $opts = [])
    {
        return false;
    }

    public function buildCacheKey(array $options = [])
    {
        return "onchain:wire:{$options['key']}:{$options['unit']}";
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
        foreach (['day', 'month'] as $unit) {
            switch ($unit) {
                case 'day':
                    $span = 17;
                    break;
                case 'month':
                    $span = 13;
                    break;
            }
            $k = Manager::buildKey([
                'aggregate' => $opts['aggregate'] ?? 'offchainwire',
                'key' => null,
                'unit' => $unit,
                'span' => $span,
            ]);
            $result[$k] = $this->fetch([
                'key' => null,
                'unit' => $unit,
                'span' => $span,
            ]);

            $avgKey = Manager::buildKey([
                'aggregate' => $opts['aggregate'] ?? 'offchainwire',
                'key' => 'avg',
                'unit' => $unit,
                'span' => $span,
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

        $key = $options['key'];

        $from = null;
        switch ($options['unit']) {
            case "day":
                $to = new DateTime('now');
                $from = (new DateTime('midnight'))
                    ->modify("-{$options['span']} days");

                $interval = '1d';
                $this->dateFormat = 'y-m-d';
                break;
            case "month":
                $to = new DateTime('midnight first day of next month');
                $from = (new DateTime())
                    ->setTimestamp($to->getTimestamp())
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
                    "transactionCategory" => [
                        "query" => "wire"
                    ]
                ]
            ],
            [
                "match_phrase" => [
                    "isTokenTransaction" => [
                        "query" => true
                    ]
                ]
            ],
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
                            "senders" => [
                                "cardinality" => [
                                    "field" => "from"
                                ]
                            ],
                            "receivers" => [
                                "cardinality" => [
                                    "field" => "to"
                                ]
                            ],
                            "tokens" => [
                                "sum" => [
                                    "field" => "tokenValue"
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
                'key' => 'transactions',
                'name' => 'OnChain Wire Transactions',
                'x' => [],
                'y' => [],
            ],
            [
                'key' => 'receivers',
                'name' => 'OnChain Wire Receivers',
                'x' => [],
                'y' => [],
            ],
            [
                'key' => 'senders',
                'name' => 'OnChain Wire Senders',
                'x' => [],
                'y' => [],
            ],
            [
                'key' => 'tokens',
                'name' => 'OnChain Plus Tokens',
                'x' => [],
                'y' => [],
            ]
        ];

        foreach ($result['aggregations']['histogram']['buckets'] as $count) {
            $date = date($this->dateFormat, $count['key'] / 1000);
            $response[0]['x'][] = $date;
            $response[0]['y'][] = $count['doc_count'] ?? 0;

            $response[1]['x'][] = $date;
            $response[1]['y'][] = $count['receivers']['value'] ?? 0;

            $response[2]['x'][] = $date;
            $response[2]['y'][] = $count['senders']['value'] ?? 0;

            $response[3]['x'][] = $date;
            $response[3]['y'][] = $count['tokens']['value'] ?? 0;
        }

        return $response;
    }
}
