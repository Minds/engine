<?php

namespace Minds\Core\Analytics\Graphs\Aggregates;

use DateTime;
use Minds\Core\Analytics\Graphs\Manager;
use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Di\Di;

class Pageviews implements AggregateInterface
{
    /** @var Client */
    protected $client;

    /** @var abstractCacher */
    protected $cacher;

    /** @var string */
    protected $index;

    /** @var string */
    protected $dateFormat;

    public function __construct($client = null, $cacher = null, $config = null)
    {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');
        $this->cacher = $cacher ?: Di::_()->get('Cache\Redis');
        $this->index = $config ? $config->get('elasticsearch')['index'] : Di::_()->get('Config')->get('elasticsearch')['metrics_index'] . '-*';
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
        foreach ([
                     null,
                     'routes',
                 ] as $key) {
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
                    'aggregate' => $opts['aggregate'] ?? 'pageviews',
                    'key' => $key,
                    'unit' => $unit,
                    'span' => $span,
                ]);
                $result[$k] = $this->fetch([
                    'key' => $key,
                    'unit' => $unit,
                    'span' => $span,
                ]);

                if ($key === null) {
                    $avgKey = Manager::buildKey([
                        'aggregate' => $opts['aggregate'] ?? 'pageviews',
                        'key' => 'avg',
                        'unit' => $unit,
                        'span' => $span,
                    ]);
                    $result[$avgKey] = Manager::calculateAverages($result[$k]);
                }
            }
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

        $response = null;

        if ($key && $key == 'routes') {
            $response = $this->getRoutes($from, $to, $interval);
        } else {
            $response = $this->getGraph($from, $to, $interval);
        }

        return $response;
    }

    public function hasTTL(array $opts = [])
    {
        return false;
    }

    public function buildCacheKey(array $opts = [])
    {
        return "pageviews:{$opts['key']}:{$opts['unit']}";
    }

    private function getRoutes($from, $to, $interval)
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
                'match_phrase' => [
                    'action.keyword' => [
                        'query' => 'pageview'
                    ]
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
                        'must' => $must
                    ]
                ],
                "aggs" => [
                    "routes" => [
                        "terms" => [
                            "field" => "route_uri.keyword",
                            'size' => 9,
                            'order' => [
                                '_count' => 'desc'
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $result = $this->client->request($prepared);

        $response = [
            [
                'name' => 'Pageviews by Route',
                'values' => [],
                'labels' => []
            ]
        ];

        $other = $result['hits']['total'];

        foreach ($result['aggregations']['routes']['buckets'] as $count) {
            $response[0]['labels'][] = $count['key'];
            $response[0]['values'][] = $count['doc_count'];
            $other -= $count['doc_count'];
        }

        if ($other > 0) {
            $response[0]['labels'][] = 'Other';
            $response[0]['values'][] = $other;
        }

        return $response;
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
                'match_phrase' => [
                    'action.keyword' => [
                        'query' => 'pageview'
                    ]
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
                        'must' => $must
                    ]
                ],
                "aggs" => [
                    "histogram" => [
                        "date_histogram" => [
                            "field" => "@timestamp",
                            'interval' => $interval,
                            'min_doc_count' => 1
                        ]
                    ],
                ]
            ]
        ];

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $result = $this->client->request($prepared);

        $response = [
            [
                'key' => 'pageviews',
                'name' => 'Pageviews',
                'x' => [],
                'y' => []
            ]
        ];

        foreach ($result['aggregations']['histogram']['buckets'] as $count) {
            $response[0]['x'][] = date($this->dateFormat, $count['key'] / 1000);
            $response[0]['y'][] = $count['doc_count'];
        }

        return $response;
    }
}
