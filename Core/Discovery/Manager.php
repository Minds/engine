<?php
namespace Minds\Core\Discovery;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Config;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Hashtags\User\Manager as HashtagManager;
use Minds\Core\Hashtags\HashtagEntity;
use Minds\Common\Repository\Response;
use Minds\Core\Feeds\Elastic\Manager as ElasticFeedsManager;
use Minds\Core\Search\SortingAlgorithms;
use Minds\Api\Exportable;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;

class Manager
{
    /** @var array */
    const GLOBAL_EXCLUDED_TAGS = [ 'minds', 'news' ];

    /** @var array */
    private $tagCloud = [];

    /** @var ElasticSearch\Client */
    private $es;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var Config */
    private $config;

    /** @var HashtagManager */
    private $hashtagManager;

    /** @var ElasticFeedsManager */
    private $elasticFeedsManager;

    /** @var User */
    protected $user;

    public function __construct(
        $es = null,
        $entitiesBuilder = null,
        $config = null,
        $hashtagManager = null,
        $elasticFeedsManager = null,
        $user = null
    ) {
        $this->es = $es ?? Di::_()->get('Database\ElasticSearch');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->config = $config ?? Di::_()->get('Config');
        $this->hashtagManager = $hashtagManager ?? Di::_()->get('Hashtags\User\Manager');
        $this->elasticFeedsManager = $elasticFeedsManager ?? Di::_()->get('Feeds\Elastic\Manager');
        $this->user = $user ?? Session::getLoggedInUser();
    }

    /**
     * Return the overview for discovery
     * @param array $opts (optional)
     * @return Trend[]
     */
    public function getTagTrends(array $opts = []): array
    {
        $opts = array_merge([
            'limit' => 10,
        ], $opts);

        $this->tagCloud = $this->getTagCloud();

        if (empty($this->tagCloud)) {
            throw new NoTagsException();
        }

        $tagTrends12 = $this->getTagTrendsForPeriod(12, [], [ 'limit' => round($opts['limit'] / 2) ]);
        $tagTrends24 = $this->getTagTrendsForPeriod(24, array_map(function ($trend) {
            return $trend->getHashtag();
        }, $tagTrends12), [ 'limit' => round($opts['limit'] / 2) ]);

        return array_merge($tagTrends12, $tagTrends24);
    }

    /**
     * @param int $hoursAgo
     * @param array $excludeTags
     * @param array $opts
     * @return Trend[]
     */
    protected function getTagTrendsForPeriod($hoursAgo, $excludeTags = [], array $opts = []): array
    {
        $opts = array_merge([
            'limit' => 10,
        ], $opts);

        $excludeTags = array_merge(self::GLOBAL_EXCLUDED_TAGS, $excludeTags);

        $query = [
            'index' => $this->config->get('elasticsearch')['index'],
            'type' => 'activity',
            'body' =>  [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'range' => [
                                    '@timestamp' => [
                                        'gte' => strtotime("$hoursAgo hours ago") * 1000,
                                    ]
                                ],
                            ],
                            [
                                'terms' => [
                                    'tags' => $this->tagCloud,
                                ]
                            ],
                        ],
                        'must_not' => [
                            [
                                'terms' => [
                                    'nsfw' => [0,1,2,3,4,5,6]
                                ]
                            ],
                        ]
                    ],
                ],
                'aggs' => [
                    'tags' => [
                        'terms' => [
                            'field' => 'tags.keyword',
                            'min_doc_count' => 10,
                            'exclude' => $excludeTags,
                            'size' => $opts['limit'],
                            'order' => [
                                'tags_per_owner' => 'desc',
                            ],
                        ],
                        'aggs' => [
                            'tags_per_owner' => [
                                'cardinality' => [
                                    'field' => 'owner_guid.keyword',
                                ]
                            ]
                        ],
                    ]
                ]
            ],
            'size' => 0
        ];

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $response = $this->es->request($prepared);

        $trends = [];
        
        foreach ($response['aggregations']['tags']['buckets'] as $bucket) {
            $tag = $bucket['key'];
            $trend = new Trend();
            $trend->setId("tag_{$tag}_{$hoursAgo}h")
                ->setHashtag($tag)
                ->setVolume($bucket['doc_count'])
                ->setPeriod($hoursAgo);
            $trends[] = $trend;
        }

        return $trends;
    }

    /**
     * Get popular popular posts
     * @param array $tags
     * @param array $opts (optional)
     * @return Trend[]
     */
    public function getPostTrends(array $tags, array $opts = []): array
    {
        $opts = array_merge([
            'hoursAgo' => 72,
            'limit' => 5,
            'shuffle' => true,
        ], $opts);

        $algorithm = new SortingAlgorithms\TopV2();

        $highlightTemplate = [
           'fragment_size' => 400,
           'number_of_fragments' => 1,
           'no_match_size' => 20
        ];

        $query = [
            'index' => $this->config->get('elasticsearch')['index'],
            'type' => 'activity',
            'body' =>  [
                'query' => [
                    'function_score' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    [
                                        'range' => [
                                            '@timestamp' => [
                                                'gte' => strtotime("{$opts['hoursAgo']} hours ago") * 1000,
                                            ]
                                        ],
                                    ],
                                    [
                                        'range' => [
                                            'comments:count' => [
                                                'gte' => 1,
                                            ]
                                        ]
                                    ],
                                    [
                                        'multi_match' => [
                                            //'type' => 'cross_fields',
                                            'query' => implode(' ', $tags),
                                            'operator' => 'OR',
                                            'fields' => ['title', 'message', 'tags^2'],
                                            'boost' => 0,
                                        ],
                                    ]
                                ],
                                'must_not' => [
                                    [
                                        'terms' => [
                                            'nsfw' => [0,1,2,3,4,5,6],
                                        ]
                                    ],
                                ]
                            ]
                        ],
                        "score_mode" => 'multiply',
                        'functions' => [
                            /*[
                            	'filter' => [
                                	'match_all' => (object) [],
                            	],
                            	'weight' => 1,
                        	],*/
                            [
                                'filter' => [
                                    'multi_match' => [
                                        'query' => implode(' ', $this->tagCloud),
                                        'operator' => 'OR',
                                        'fields' => ['title', 'message', 'tags'],
                                        'boost' => 0,
                                    ],
                                ],
                                'weight' => 2,
                            ],
                            [
                                'filter' => [
                                    'terms' => [
                                        'subtype' => [ 'video', 'blog' ],
                                    ]
                                ],
                                'weight' => 10, // videos and blogs are worth 10x
                            ],
                            [
                                'field_value_factor' => [
                                    'field' => 'comments:count',
                                    'factor' => 10,
                                    'modifier' => 'sqrt',
                                    'missing' => 0,
                                ],
                            ],
                            [
                                'gauss' => [
                                    '@timestamp' => [
                                        'offset' => '6h', // Do not decay until we reach this bound
                                        //'offset' => $opts['hoursAgo'] . 'h',
                                        'scale' => '24h', // Peak decay will be here
                                        //'scale' => '12h',
                                        //'decay' => rand(1, 9) / 10
                                    ],
                                ],
                                'weight' => 10,
                            ]
                        ],
                    ]
                ],
                "collapse" => [
                    "field" => "owner_guid.keyword"
                ],
                "highlight" => [
                     "pre_tags" => [
                        "<span class='m-highlighted'>"
                     ],
                     "post_tags" => [
                        "</span>"
                     ],
                     "fields" => [
                        "title" => $highlightTemplate,
                        "message" => $highlightTemplate,
                        "tags" => $highlightTemplate,
                     ]
                ],
                'sort' => [
                    [
                        '_score' => [
                            'order' => 'desc'
                        ],
                    ]
                ]
            ],
            'size' => $opts['limit'] * 3, // * 3 because not all have thumbnails (improve our indexing!)
        ];

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $response = $this->es->request($prepared);
 
        $trends = [];
        foreach ($response['hits']['hits'] as $doc) {
            $ownerGuid = $doc['_source']['owner_guid'];
            
            $title = $doc['_source']['title'] ?: $doc['_source']['message'];

            shuffle($doc['_source']['tags']);
            $hashtag = $doc['_source']['tags'][0];

            $entity = $this->entitiesBuilder->single($doc['_id']);

            $exportedEntity = $entity->export();
            if (!$exportedEntity['thumbnail_src']) {
                error_log("{$exportedEntity['guid']} has not thumbnail");
                continue;
            }

            $trend = new Trend();
            $trend->setGuid($doc['_id'])
                ->setTitle($title)
                ->setId($doc['_id'])
                ->setEntity($entity)
                ->setVolume($doc['_source']['comments:count'])
                ->setHashtag($hashtag)
                ->setPeriod((time() - $entity->getTimeCreated()) / 3600);

            $trends[] = $trend;

            if (count($trends) >= $opts['limit']) {
                break;
            }
        }

        if ($opts['shuffle']) {
            shuffle($trends);
        }

        return $trends;
    }

    /**
     * Return entities for a search query and filter
     * @param string $query
     * @param string $filter
     * @return Response
     */
    public function getSearch(string $query, string $filter, string $type = 'activity'): Response
    {
        $algorithm = 'latest';

        switch ($type) {
            case 'blogs':
                $type = 'object:blog';
                break;
            case 'images':
                $type = 'object:image';
                break;
            case 'videos':
                $type = 'object:video';
                break;
            default:
                $type = 'activity';
                break;
        }

        switch ($filter) {
            case 'top':
                $algorithm = 'topV2';
                break;
            case 'channels':
                $type = 'user';
                break;
            case 'groups':
                $type = 'group';
                break;
        }

        $elasticEntities = new Core\Feeds\Elastic\Entities();
        
        $opts = [
            'cache_key' => $this->user->getGuid(),
            'access_id' => 2,
            'limit' => 5000,
            //'offset' => $offset,
            'nsfw' => [],
            'type' => $type,
            'algorithm' => $algorithm,
            'period' => '1y',
            'query' => $query,
        ];

        $rows = $this->elasticFeedsManager->getList($opts);

        $entities = new Response();
        $entities = $entities->pushArray($rows->toArray());

        if ($type === 'user') {
            foreach ($entities as $entity) {
                $entity->getEntity()->exportCounts = true;
            }
        }

        return $entities;
    }

    /**
     * Returns the preferred and trending tags
     * @return array
     */
    public function getTags(): array
    {
        $tagsList = $this->hashtagManager
            ->setUser($this->user)
            ->get([
                'defaults' => true,
                'trending' => true,
                'limit' => 20,
            ]);

        $tags = array_filter($tagsList, function ($tag) {
            return $tag['type'] === 'user';
        });

        $trending = array_filter($tagsList, function ($tag) {
            return $tag['type'] === 'trending' || $tag['type'] === 'default';
        });

        return [
            'tags' => array_values($tags),
            'trending' => array_values($trending),
        ];
    }

    /**
     * Return tagcloud
     * @return array
     */
    protected function getTagCloud(): array
    {
        return array_map(function ($tag) {
            return $tag['value'];
        }, $this->hashtagManager
            ->setUser($this->user)
            ->get([
                'defaults' => false,
            ]));
    }

    /**
     * Set the tags a user wants to subscribe to
     * @param array $selected
     * @param array $deslected
     * @return bool
     */
    public function setTags(array $selected, array $deselected): bool
    {
        $add = array_map(function ($tag) {
            return (new HashtagEntity)
               ->setGuid($this->user->getGuid())
               ->setHashtag($tag);
        }, $selected);

        $remove = array_map(function ($tag) {
            return (new HashtagEntity)
               ->setGuid($this->user->getGuid())
               ->setHashtag($tag);
        }, $deselected);

        return $this->hashtagManager
           ->setUser($this->user)
          ->batch($add, $remove);
    }
}
