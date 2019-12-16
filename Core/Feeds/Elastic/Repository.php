<?php

namespace Minds\Core\Feeds\Elastic;

use Minds\Core\Data\ElasticSearch\Client as ElasticsearchClient;
use Minds\Core\Data\ElasticSearch\Prepared;
use Minds\Core\Di\Di;
use Minds\Core\Features\Manager as Features;
use Minds\Core\Search\SortingAlgorithms;
use Minds\Helpers\Text;

class Repository
{
    const PERIODS = [
        '12h' => 43200,
        '24h' => 86400,
        '7d' => 604800,
        '30d' => 2592000,
        '1y' => 31536000,
        'all' => -1,
    ];

    /** @var ElasticsearchClient */
    protected $client;

    /** @var Features */
    protected $features;

    /** @var string */
    protected $index;

    /** @var array $pendingBulkInserts * */
    private $pendingBulkInserts = [];

    public function __construct($client = null, $config = null, $features = null)
    {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');

        $config = $config ?: Di::_()->get('Config');

        $this->features = $features ?: Di::_()->get('Features');

        $this->index = $config->get('elasticsearch')['index'];
    }

    /**
     * @param array $opts
     * @return \Generator|ScoredGuid[]
     * @throws \Exception
     */
    public function getList(array $opts = [])
    {
        $opts = array_merge([
            'offset' => 0,
            'limit' => 12,
            'container_guid' => null,
            'owner_guid' => null,
            'subscriptions' => null,
            'access_id' => null,
            'custom_type' => null,
            'hashtags' => [],
            'filter_hashtags' => true,
            'type' => null,
            'period' => null,
            'algorithm' => null,
            'query' => null,
            'nsfw' => null,
            'from_timestamp' => null,
            'exclude_moderated' => false,
            'moderation_reservations' => null,
            'pinned_guids' => null,
            'future' => false,
            'exclude' => null,
        ], $opts);

        if (!$opts['type']) {
            throw new \Exception('Type must be provided');
        }

        if (!$opts['algorithm']) {
            throw new \Exception('Algorithm must be provided');
        }

        if (!in_array($opts['period'], array_keys(static::PERIODS), true)) {
            throw new \Exception('Unsupported period');
        }

        $type = $opts['type'];

        $body = [
            '_source' => array_unique([
                'guid',
                'owner_guid',
                '@timestamp',
                'time_created',
                'access_id',
                'moderator_guid',
                $this->getSourceField($type),
            ]),
            'query' => [
                'function_score' => [
                    'query' => [
                        'bool' => [
                            //'must_not' => [ ],
                        ],
                    ],
                    "score_mode" => "sum",
                    'functions' => [
                        [
                            'filter' => [
                                'match_all' => (object) [],
                            ],
                            'weight' => 1,
                        ],
                    ],
                ],
            ],
            'sort' => [],
        ];

        //

        switch ($opts['algorithm']) {
            case "top":
                if ($this->features->has('top-feeds-by-age')) {
                    $algorithm = new SortingAlgorithms\TopByPostAge();
                } else {
                    $algorithm = new SortingAlgorithms\Top();
                }
                break;
            case "controversial":
                $algorithm = new SortingAlgorithms\Controversial();
                break;
            case "hot":
                $algorithm = new SortingAlgorithms\Hot();
                break;
            case "latest":
            default:
                $algorithm = new SortingAlgorithms\Chronological();
                break;
        }

        $algorithm->setPeriod($opts['period']);

        //

        if ($opts['container_guid']) {
            $containerGuids = Text::buildArray($opts['container_guid']);

            if (!isset($body['query']['function_score']['query']['bool']['must'])) {
                $body['query']['function_score']['query']['bool']['must'] = [];
            }

            $body['query']['function_score']['query']['bool']['must'][] = [
                'terms' => [
                    'container_guid' => $containerGuids,
                ],
            ];
        }

        if ($opts['owner_guid']) {
            $ownerGuids = Text::buildArray($opts['owner_guid']);

            if (!isset($body['query']['function_score']['query']['bool']['must'])) {
                $body['query']['function_score']['query']['bool']['must'] = [];
            }

            $body['query']['function_score']['query']['bool']['must'][] = [
                'terms' => [
                    'owner_guid' => $ownerGuids,
                ],
            ];
        } elseif ($opts['subscriptions']) {
            if (!isset($body['query']['function_score']['query']['bool']['must'])) {
                $body['query']['function_score']['query']['bool']['must'] = [];
            }

            $body['query']['function_score']['query']['bool']['must'][] = [
                'bool' => [
                    'should' => [
                        [
                            'terms' => [
                                'owner_guid' => [
                                    'index' => 'minds-graph',
                                    'type' => 'subscriptions',
                                    'id' => (string) $opts['subscriptions'],
                                    'path' => 'guids',
                                ],
                            ],
                        ],
                        [
                            'term' => [
                                'owner_guid' => (string) $opts['subscriptions'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (!$opts['container_guid'] && !$opts['owner_guid']) {
            if (!isset($body['query']['function_score']['query']['bool']['must_not'])) {
                $body['query']['function_score']['query']['bool']['must_not'] = [];
            }

            $body['query']['function_score']['query']['bool']['must_not'][] = [
                'term' => [
                    'deleted' => true,
                ],
            ];
        }

        if ($opts['custom_type']) {
            $customTypes = Text::buildArray($opts['custom_type']);

            if (!isset($body['query']['function_score']['query']['bool']['must'])) {
                $body['query']['function_score']['query']['bool']['must'] = [];
            }

            $body['query']['function_score']['query']['bool']['must'][] = [
                'terms' => [
                    'custom_type' => $customTypes,
                ],
            ];
        }

        if ($opts['nsfw'] !== null) {
            $nsfw = array_diff([1, 2, 3, 4, 5, 6], $opts['nsfw']);
            if ($nsfw) {
                $body['query']['function_score']['query']['bool']['must_not'][] = [
                    'terms' => [
                        'nsfw' => array_values($nsfw),
                    ],
                ];

                if (in_array(6, $nsfw, false)) { // 6 is legacy 'mature'
                    $body['query']['function_score']['query']['bool']['must_not'][] = [
                        'term' => [
                            'mature' => true,
                        ],
                    ];
                }
            }
        }

        if ($type !== 'group' && $opts['access_id'] !== null) {
            $body['query']['function_score']['query']['bool']['must'][] = [
                'terms' => [
                    'access_id' => Text::buildArray($opts['access_id']),
                ],
            ];
        }

        if ($type === 'group') {
            $body['query']['function_score']['query']['bool']['must'][] = [
                'range' => [
                    'access_id' => [
                        'gt' => 2,
                    ]
                ]
            ];
        }

        // Time bounds

        $timestampUpperBounds = []; // LTE
        $timestampLowerBounds = []; // GT

        if ($algorithm->isTimestampConstrain() && static::PERIODS[$opts['period']] > -1) {
            $timestampLowerBounds[] = (time() - static::PERIODS[$opts['period']]) * 1000;
        }

        if ($opts['from_timestamp']) {
            $timestampUpperBounds[] = (int) $opts['from_timestamp'];
        }

        if ($opts['future']) {
            $timestampLowerBounds[] = time() * 1000;
        } else {
            $timestampUpperBounds[] = time() * 1000;
        }

        if ($timestampUpperBounds || $timestampLowerBounds) {
            if (!isset($body['query']['function_score']['query']['bool']['must'])) {
                $body['query']['function_score']['query']['bool']['must'] = [];
            }

            $range = [];

            if ($timestampUpperBounds) {
                $range['lte'] = min($timestampUpperBounds);
            }

            if ($timestampLowerBounds) {
                $range['gt'] = max($timestampLowerBounds);
            }

            $body['query']['function_score']['query']['bool']['must'][] = [
                'range' => [
                    '@timestamp' => $range,
                ],
            ];
        }

        //

        if ($opts['query']) {
            $words = explode(' ', $opts['query']);

            if (count($words) === 1) {
                $body['query']['function_score']['query']['bool']['must'][] = [
                    'multi_match' => [
                        'query' => $opts['query'],
                        'fields' => ['name^2', 'title^12', 'message^12', 'description^12', 'brief_description^8', 'username^8', 'tags^64'],
                    ],
                ];
            } else {
                $body['query']['function_score']['query']['bool']['must'][] = [
                    'multi_match' => [
                        'query' => $opts['query'],
                        'type' => 'phrase',
                        'fields' => ['name^2', 'title^12', 'message^12', 'description^12', 'brief_description^8', 'username^8', 'tags^16'],
                    ],
                ];
            }
        } elseif ($opts['hashtags']) {
            if (!isset($body['query']['function_score']['query']['bool']['must'])) {
                $body['query']['function_score']['query']['bool']['must'] = [];
            }

            $body['query']['function_score']['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => implode(' ', $opts['hashtags']),
                    'fields' => ['name^2', 'title^12', 'message^12', 'description^12', 'brief_description^8', 'username^8', 'tags^64'],
                    'operator' => 'or',
                    'minimum_should_match' => 1,
                ],
            ];
            $body['query']['function_score']['boost_mode'] = 'replace';
        }

        if ($opts['exclude']) {
            $body['query']['function_score']['query']['bool']['must_not'][] = [
                'terms' => [
                    'guid' => Text::buildArray($opts['exclude']),
                ],
            ];
        }


        // firehose options

        if ($opts['exclude_moderated']) {
            $body['query']['function_score']['query']['bool']['must_not'][] = ['exists' => ['field' => 'moderator_guid']];
        }
       
        if ($opts['moderation_reservations']) {
            $body['query']['function_score']['query']['bool']['must_not'][] = [
                'terms' => [
                    'guid' => $opts['moderation_reservations'],
                ],
            ];
        }

        //

        $esQuery = $algorithm->getQuery();
        if ($esQuery) {
            $body['query']['function_score']['query'] = array_merge_recursive($body['query']['function_score']['query'], $esQuery);
        }

        //

        $esScript = $algorithm->getScript();
        if ($esScript) {
            $body['query']['function_score']['functions'][] = [
                'script_score' => [
                    'script' => [
                        'source' => $esScript,
                    ],
                ],
            ];
        }

        //

        $esSort = $algorithm->getSort();
        if ($esSort) {
            $body['sort'][] = $esSort;
        }

        //

        $esType = $opts['type'];

        if ($type === 'user' || $type === 'group') {
            $esType = 'activity,object:image,object:video,object:blog';
        }

        if ($esType === 'all') {
            $esType = 'object:image,object:video,object:blog';
        }

        $query = [
            'index' => $this->index,
            'type' => $esType,
            'body' => $body,
            'size' => $opts['limit'],
            'from' => $opts['offset'],
        ];

        $prepared = new Prepared\Search();
        $prepared->query($query);

        $response = $this->client->request($prepared);

        if ($opts['pinned_guids']) { // Hack the response so we can have pinned posts
            foreach ($opts['pinned_guids'] as $pinned_guid) {
                array_unshift($response['hits']['hits'], [
                    '_type' => 'activity',
                    '_source' => [
                        'guid' => $pinned_guid,
                        'owner_guid' => null,
                        'score' => 0,
                        'timestamp' => 0,
                    ],
                ]);
            }
        }

        $docs = $response['hits']['hits'];

        // Sort channels / groups by post scores
        if ($type === 'user' || $type === 'group') {
            $newDocs = []; // New array so we return only users and groups, not posts
            foreach ($docs as $doc) {
                $key = $doc['_source'][$this->getSourceField($type)];
                $newDocs[$key] = $newDocs[$key] ?? [
                    '_source' => [
                        'guid' => $key,
                        'owner_guid' => $key,
                        $this->getSourceField($type) => $key,
                        '@timestamp' => $doc['_source']['@timestamp'],
                    ],
                    '_type' => $type,
                    '_score' => 0,
                ];
                $newDocs[$key]['_score'] = log10($newDocs[$key]['_score'] + $algorithm->fetchScore($doc));
            }
            $docs = $newDocs;
        }

        $guids = [];
        foreach ($docs as $doc) {
            $guid = $doc['_source'][$this->getSourceField($opts['type'])];
            if (isset($guids[$guid])) {
                continue;
            }
            $guids[$guid] = true;
            yield (new ScoredGuid())
                ->setGuid($doc['_source'][$this->getSourceField($opts['type'])])
                ->setType($doc['_type'])
                ->setScore($algorithm->fetchScore($doc))
                ->setOwnerGuid($doc['_source']['owner_guid'])
                ->setTimestamp($doc['_source']['@timestamp']);
        }
    }

    private function getSourceField(string $type)
    {
        switch ($type) {
            case 'user':
                return 'owner_guid';
                break;
            case 'group':
                return 'container_guid';
                break;
            default:
                return 'guid';
                break;
        }
    }

    public function add(MetricsSync $metric)
    {
        $key = $metric->getMetric();

        if ($metric->getPeriod()) {
            $key .= ":{$metric->getPeriod()}";
        }

        $body = [
            $key => $metric->getCount(),
            "{$key}:synced" => $metric->getSynced()
        ];

        $this->pendingBulkInserts[] = [
            'update' => [
                '_id' => (string) $metric->getGuid(),
                '_index' => 'minds_badger',
                '_type' => $metric->getType(),
            ],
        ];

        $this->pendingBulkInserts[] = [
            'doc' => $body,
            'doc_as_upsert' => true,
        ];

        if (count($this->pendingBulkInserts) > 2000) { //1000 inserts
            $this->bulk();
        }

        return true;
    }

    /**
     * Run a bulk insert job (quicker).
     */
    public function bulk()
    {
        if (count($this->pendingBulkInserts) > 0) {
            $res = $this->client->bulk(['body' => $this->pendingBulkInserts]);
            $this->pendingBulkInserts = [];
        }
    }
}
