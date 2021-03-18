<?php

namespace Minds\Core\Analytics\Graphs\Aggregates;

use DateTime;
use Minds\Core\Analytics\Graphs\Manager;
use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Di\Di;

class Reminds implements AggregateInterface
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
        $this->index = $config ? $config->get('elasticsearch')['metrics_index'] : Di::_()->get('Config')->get('elasticsearch')['metrics_index'] . '-*';
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
        foreach (['hour', 'day', 'month'] as $unit) {
            switch ($unit) {
                case 'hour':
                    $span = 25;
                    break;
                case 'day':
                    $span = 17;
                    break;
                case 'month':
                    $span = 13;
                    break;
            }
            $k = Manager::buildKey([
                'aggregate' => $opts['aggregate'] ?? 'reminds',
                'key' => null,
                'unit' => $unit,
                'span' => $span,
            ]);
            $result[$k] = $this->fetch([
                'unit' => $unit,
                'span' => $span,
            ]);

            $avgKey = Manager::buildKey([
                'aggregate' => $opts['aggregate'] ?? 'reminds',
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
            'userGuid' => null,
        ], $options);

        $userGuid = $options['userGuid'];

        $from = null;
        switch ($options['unit']) {
            case "hour":
                $to = new DateTime('now');
                $from = (new DateTime())
                    ->setTimestamp($to->getTimestamp())
                    ->modify("-{$options['span']} hours");

                $interval = '1h';
                $this->dateFormat = 'y-m-d H:i';
                break;
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
                    "action.keyword" => [
                        "query" => "remind"
                    ]
                ]
            ]
        ];

        // filter by user_guid
        if ($userGuid) {
            $must[]['match'] = [
                'entity_owner_guid.keyword' => $userGuid
            ];
        }

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
                    "reminds" => [
                        "date_histogram" => [
                            "field" => "@timestamp",
                            "interval" => $interval,
                            "min_doc_count" => 1
                        ],
                        "aggs" => [
                            "unique" => [
                                "cardinality" => [
                                    "field" => "user_guid.keyword"
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        ];

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $result = $this->client->request($prepared);

        $response = [
            [
                'key' => 'reminds',
                'name' => 'Reminds',
                'x' => [],
                'y' => []
            ]
        ];

        if (!$userGuid) {
            $response[] = [
                'key' => 'remindingUsers',
                'name' => 'Reminding Users',
                'x' => [],
                'y' => []
            ];
        }

        foreach ($result['aggregations']['reminds']['buckets'] as $count) {
            $date = date($this->dateFormat, $count['key'] / 1000);
            $response[0]['x'][] = $date;
            $response[0]['y'][] = $count['doc_count'];

            if (!$userGuid) {
                $response[1]['x'][] = $date;
                $response[1]['y'][] = $count['unique']['value'];
            }
        }

        return $response;
    }

    public function hasTTL(array $opts = [])
    {
        return isset($opts['userGuid']);
    }

    public function buildCacheKey(array $options = [])
    {
        return "reminds:{$options['unit']}:{$options['userGuid']}";
    }
}
