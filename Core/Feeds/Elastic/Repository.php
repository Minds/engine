<?php

namespace Minds\Core\Feeds\Elastic;

use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch\Client as ElasticsearchClient;
use Minds\Core\Data\ElasticSearch\Prepared;
use Minds\Core\Di\Di;
use Minds\Core\Features\Manager as Features;
use Minds\Core\Search\SortingAlgorithms;
use Minds\Helpers\Text;

class Repository
{
    /* Change to true to output ES query in logs */
    public const DEBUG = false;

    public const PERIODS = [
        '12h' => 43200,
        '24h' => 86400,
        '7d' => 604800,
        '30d' => 2592000,
        '1y' => 31536000,
        'all' => -1,
        'relevant' => -1,
    ];

    /** @var ElasticsearchClient */
    protected $client;

    /** @var Features */
    protected $features;

    /** @var string */
    protected $index;

    /** @var array $pendingBulkInserts * */
    private $pendingBulkInserts = [];

    /** @var string */
    private $plusSupportTierUrn;

    private Config $config;

    public function __construct($client = null, $config = null, $features = null)
    {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');

        $this->config = $config ?? Di::_()->get('Config');

        $this->features = $features ?: Di::_()->get('Features\Manager');

        $this->index = $this->config->get('elasticsearch')['indexes']['search_prefix'];

        $this->plusSupportTierUrn = $this->config->get('plus')['support_tier_urn'] ?? null;
    }

    /**
     * @param array $opts
     * @return \Generator|ScoredGuid[]
     * @throws \Exception
     */
    public function getList(array $opts = [])
    {
        $built = $this->buildQuery($opts);

        // Using an typed object would be nice
        // but don't have time
        $query = $built['query'];
        $type = $built['type'];
        $algorithm = $built['algorithm'];

        $prepared = new Prepared\Search();
        $prepared->query($query);

        if (static::DEBUG) {
            error_log("Querying ES with: \n".json_encode($query));
        }

        $response = $this->client->request($prepared);

        if (($opts['pinned_guids'] ?? null) && !$opts['from_timestamp']) { // Hack the response so we can have pinned posts
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
                        'type' => $type,
                    ],
                    '_type' => $type,
                    '_score' => 0,
                    '_index' => $this->index . '-' . $type,
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
                ->setType(str_replace($this->index . '-', '', $doc['_index']))
                ->setScore($algorithm->fetchScore($doc))
                ->setOwnerGuid($doc['_source']['owner_guid'])
                ->setTimestamp($doc['_source']['@timestamp']);
        }
    }

    /**
     * Returns a count of the query
     * @param array $opts
     * @return
     */
    public function getCount(array $opts = []): int
    {
        $built = $this->buildQuery($opts);

        // Using an typed object would be nice
        // but don't have time
        $query = $built['query'];
        unset($query['size']);
        unset($query['from']);
        unset($query['body']['_source']);
        unset($query['body']['sort']);

        $prepared = new Prepared\Count();
        $prepared->query($query);

        try {
            $response = $this->client->request($prepared);

            return $response['count'];
        } catch (\Exception $e) {
            return 0;
            // TODO: Log this error, why did it fail
        }
    }

    /**
     * Too monolithic of a function, but it does the job
     * @param array $opts
     * @return array
     */
    protected function buildQuery(array $opts)
    {
        $opts = array_merge([
            'offset' => 0,
            'limit' => 12,
            'container_guid' => null,
            'owner_guid' => null,
            'subscriptions' => null,
            'hide_own_posts' => false,
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
            'to_timestamp' => null,
            'reverse_sort' => null,
            'exclude_moderated' => false,
            'moderation_reservations' => null,
            'pinned_guids' => null,
            'future' => false,
            'exclude' => null,
            'pending' => false,
            'plus' => false,
            'portrait' => false,
            'hide_reminds' => false,
            'wire_support_tier_only' => false,
            // Focus on reminds
            'remind_guid' => null,
            // Focus on quotes
            'quote_guid' => null,
            'include_group_posts' => false,
        ], $opts);

        if (!$opts['type']) {
            //   throw new \Exception('Type must be provided');
        }

        if (!$opts['algorithm']) {
            throw new \Exception('Algorithm must be provided');
        }

        if (!in_array($opts['period'], array_keys(static::PERIODS), true)) {
            throw new \Exception('Unsupported period');
        }

        $type = $opts['type'];


        //

        switch ($opts['algorithm']) {
            case "top":
                if ($this->features->has('top-feeds-by-age')) {
                    $algorithm = new SortingAlgorithms\TopByPostAge();
                } else {
                    $algorithm = new SortingAlgorithms\Top();
                }
                if ($this->features->has('topv2-algo')) {
                    $algorithm = new SortingAlgorithms\TopV2();
                }
                break;
            case "topV2":
                $algorithm = new SortingAlgorithms\TopV2();
                break;
            case "controversial":
                $algorithm = new SortingAlgorithms\Controversial();
                break;
            case "hot":
                $algorithm = new SortingAlgorithms\Hot();
                if ($this->features->has('topv2-algo')) {
                    $algorithm = new SortingAlgorithms\TopV2();
                }
                break;
            case SortingAlgorithms\DigestFeed::class:
                $algorithm = new SortingAlgorithms\DigestFeed();
                break;
            case SortingAlgorithms\PlusFeed::class:
            case "plusFeed":
                $algorithm = new SortingAlgorithms\PlusFeed();
                break;
            case "latest":
            default:
                $algorithm = new SortingAlgorithms\Chronological();
                break;
        }

        $algorithm->setPeriod($opts['period']);

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
                    "score_mode" => $algorithm->getScoreMode(),
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

            $should = [];

            // Subquery to get the subscription content
            $should[] = [
                'terms' => [
                    'owner_guid' => [
                        'index' => 'minds-graph-subscriptions',
                        'id' => (string) $opts['subscriptions'],
                        'path' => 'guids',
                    ],
                ],
            ];

            if ($opts['include_group_posts']) {
                $should[] = [
                    'terms' => [
                        'container_guid' => [
                            'index' => $this->index.'-user',
                            'id' => (string) $opts['subscriptions'],
                            'path' => 'group_membership',
                        ],
                    ]
                ];
            }

            // Will return own posts if requested
            if ($opts['hide_own_posts']) {
                if (!isset($body['query']['function_score']['query']['bool']['must_not'])) {
                    $body['query']['function_score']['query']['bool']['must_not'] = [];
                }
                $body['query']['function_score']['query']['bool']['must_not'][] = [
                    'term' => [
                        'owner_guid' => (string) $opts['subscriptions'],
                    ],
                ];
            } else {
                $should[] = [
                    'term' => [
                        'owner_guid' => (string) $opts['subscriptions'],
                    ],
                ];
            }

            $body['query']['function_score']['query']['bool']['must'][] = [
                'bool' => [
                    'should' => $should,
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

        // Hide reminds (note, this will not hide quoted posts)
        if ($opts['hide_reminds'] === true) {
            $body['query']['function_score']['query']['bool']['must_not'][] = [
                'term' => [
                    'is_remind' => true,
                ],
            ];
        }

        if ($opts['wire_support_tier_only']) {
            error_log("memberships only");
            $body['query']['function_score']['query']['bool']['must'][] = [
                'exists' => ['field' => 'wire_support_tier']
            ];
        }

        if ($type !== 'group' && $opts['access_id'] !== null && !$opts['include_group_posts']) {
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

        if ($opts['include_group_posts']) {
            $body['query']['function_score']['query']['bool']['must'][] = [
                'bool' => [
                    'should' => [
                        [
                            'range' => [
                                'access_id' => [
                                    'gte' => 2,
                                ],
                            ],
                        ],
                        [
                            'terms' => [
                                'access_id' => [
                                    'index' => $this->index.'-user',
                                    'id' => (string) $opts['subscriptions'],
                                    'path' => 'group_membership',
                                ],
                            ]
                        ],
                    ],
                ],
            ];
        }

        if ($opts['pending'] === false) {
            $body['query']['function_score']['query']['bool']['must_not'][] = [
                'term' => [
                    'pending' => true,
                ]
            ];
        }

        // Plus?
        if ($opts['plus'] === true) {
            $body['query']['function_score']['query']['bool']['must'][] = [
                'term' => [
                    'wire_support_tier' => $this->plusSupportTierUrn,
                ],
            ];
        }

        // Portrait only?
        if ($opts['portrait'] === true) {
            $body['query']['function_score']['query']['bool']['must'][] = [
                'term' => [
                    'is_portrait' => true,
                ],
            ];
        }

        // Time bounds

        $timestampUpperBounds = []; // LTE
        $timestampLowerBounds = []; // GTE

        if ($algorithm->isTimestampConstrain() && static::PERIODS[$opts['period']] > -1) {
            $timestampLowerBounds[] = (time() - static::PERIODS[$opts['period']]) * 1000;
        }

        // Will start the feed after this timestamp (used for pagination)
        if ($opts['from_timestamp'] && !$opts['to_timestamp']) {
            if (!$opts['reverse_sort']) {
                $timestampUpperBounds[] = (int) $opts['from_timestamp'];
            } else {
                $timestampLowerBounds[] = (int) $opts['from_timestamp'];
            }
        }

        // Load the feed between these two timestamps
        if ($opts['from_timestamp'] && $opts['to_timestamp']) {
            $timestampUpperBounds[] = (int) $opts['from_timestamp'];
            $timestampLowerBounds[] = (int) $opts['to_timestamp'];
        }

        if (!$opts['to_timestamp']) {
            if ($opts['future']) {
                $timestampLowerBounds[] = time() * 1000;
            } else {
                $timestampUpperBounds[] = time() * 1000;
            }
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
                $range['gte'] = max($timestampLowerBounds);
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
                        'fields' => ['name^2', 'title^12', 'message^12', 'description^12', 'brief_description^8', 'username^8', 'tags^12'],
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
            if (!isset($body['query']['function_score']['query']['bool']['should'])) {
                $body['query']['function_score']['query']['bool']['should'] = [];
            }

            $body['query']['function_score']['query']['bool']['should'][] = [
                'terms' => [
                    'tags' => $opts['hashtags'],
                    'boost' => 1, // hashtags are 10x more valuable then non-hashtags
                ],
            ];
            $body['query']['function_score']['query']['bool']['should'][] = [
                'multi_match' => [
                    'query' => implode(' ', $opts['hashtags']),
                    'fields' => ['title', 'message', 'description'],
                    'operator' => 'or',
                    'boost' => 0.1
                ],
            ];
            $body['query']['function_score']['query']['bool']['minimum_should_match'] = 1;
        }

        if ($opts['exclude']) {
            if ($opts['demoted']) {
                $body['query']['function_score']['functions'][] = [
                    'filter' => [
                        'terms' => [
                            'guid' => Text::buildArray($opts['exclude'])
                        ]
                    ],
                    'weight' => $this->config->get('seen-entities-weight') ?? 0.01
                ];
            } else {
                $body['query']['function_score']['query']['bool']['must_not'][] = [
                    'terms' => [
                        'guid' => Text::buildArray($opts['exclude']),
                    ],
                ];
            }
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

        if ($opts['remind_guid']) {
            $body['query']['function_score']['query']['bool']['must'][] = [
                'term' => [
                    'remind_guid' => (string) $opts['remind_guid'],
                ]
            ];
            $body['query']['function_score']['query']['bool']['must'][] = [
                'term' => [
                    'is_remind' => true
                ]
            ];
        }

        //

        if ($opts['quote_guid']) {
            $body['query']['function_score']['query']['bool']['must'][] = [
                'term' => [
                    'remind_guid' => (string) $opts['quote_guid'], // Note: remind_guid is correct, filter below by is_quote_post
                ]
            ];
            $body['query']['function_score']['query']['bool']['must'][] = [
                'term' => [
                    'is_quoted_post' => true,
                ]
            ];
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

        if ($functionScores = $algorithm->getFunctionScores()) {
            foreach ($functionScores as $functionScore) {
                $body['query']['function_score']['functions'][] = $functionScore;
            }
        }

        //

        $esSort = $algorithm->getSort();
        if ($esSort) {
            if ($opts['reverse_sort']) {
                $esSort = $this->reverseSort($esSort);
            }

            $body['sort'][] = $esSort;
        }

        //

        $esType = $opts['type'] ?: 'all';

        $index = $this->index . '-';

        if ($esType === 'all') {
            $index = implode(',', array_map(function ($type) {
                return $this->index . '-' . $type;
            }, [
                'activity',
                'object-image',
                'object-video',
                'object-blog',
            ]));
        } elseif ($esType === 'object-*') {
            $index = implode(',', array_map(function ($type) {
                return $this->index . '-' . $type;
            }, [
                'object-image',
                'object-video',
                'object-blog',
            ]));
        } else {
            $index .= $esType;
        }

        $query = [
            'index' => $index,
            'body' => $body,
            'size' => $opts['limit'],
            'from' => $opts['offset'],
        ];

        return [
            'query' => $query,
            'type' => $type,
            'algorithm' => $algorithm,
        ];
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

    private function reverseSort(array $sort)
    {
        foreach ($sort as $field => $opts) {
            if (isset($opts['order'])) {
                $opts['order'] = $opts['order'] == 'asc' ? 'desc' : 'asc';
            }

            $sort[$field] = $opts;
        }

        return $sort;
    }
}
