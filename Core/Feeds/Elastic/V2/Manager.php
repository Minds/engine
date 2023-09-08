<?php
namespace Minds\Core\Feeds\Elastic\V2;

use Minds\Core\Data\ElasticSearch;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Feeds\ClusteredRecommendations;
use Minds\Core\Feeds\Elastic\V2\Enums\MediaTypeEnum;
use Minds\Core\Feeds\Elastic\V2\Enums\SeenEntitiesFilterStrategyEnum;
use Minds\Core\Feeds\Seen\Manager as SeenManager;
use Minds\Core\Groups\V2\Membership;
use Minds\Core\Guid;
use Minds\Core\Search\SortingAlgorithms\TopV2;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Helpers\Text;

class Manager
{
    public function __construct(
        protected ElasticSearch\Client $esClient,
        protected ClusteredRecommendations\MySQLRepository $clusteredRecsRepository,
        protected SeenManager $seenManager,
        protected EntitiesBuilder $entitiesBuilder,
        protected Membership\Manager $groupsMembershipManager,
        protected ACL $acl,
        private readonly ExperimentsManager $experimentsManager,
    ) {
    }

    /**
     * Returns the count for the query
     */
    public function getLatestCount(
        QueryOpts $queryOpts,
        string &$loadAfter = null,
        string &$loadBefore = null,
        bool &$hasMore = null
    ): int {
        $prepared = $this->prepareElastic($queryOpts);
        $must = $prepared['must'];
        $should = $prepared['should'];

        $hasMore = true;

        if ($loadAfter || $loadBefore) {
            $cursorData = $this->decodeSort($loadAfter ?: $loadBefore);
            $timestamp = $cursorData[0];
            $op = $loadAfter ? 'lt' : 'gt';

            $must[] = [
                'range' => [
                    '@timestamp' => [
                        $op => $timestamp,
                    ]
                ]
            ];
        }

        $body = [
            'query' => [
                'bool' => [
                    'must' => $must,
                    'should' => $should,
                    'minimum_should_match' => count($should) > 0 ? 1 : 0,
                ],
            ],
        ];

        $query = [
            'index' => $this->getSearchIndexName(),
            'body' => $body,
        ];

        $prepared = new ElasticSearch\Prepared\Count();
        $prepared->query($query);

        // Setup cursors
        $loadAfter = $loadBefore;
        $loadBefore = $this->encodeSort([time() * 1000, Guid::build()]);

        $response = $this->esClient->request($prepared);

        return $response['count'] ?? 0;
    }

    /**
     * Returns the latest posts for the query
     * @return iterable<Activity>
     */
    public function getLatest(
        QueryOpts $queryOpts,
        string &$loadAfter = null,
        string &$loadBefore = null,
        bool &$hasMore = null
    ): iterable {
        $prepared = $this->prepareElastic($queryOpts);

        $limit = $queryOpts->limit;
        $must = $prepared['must'];
        $mustNot = $prepared['mustNot'];
        $should = $prepared['should'];

        if ($loadAfter && $loadBefore) {
            throw new ServerErrorException("Two cursors, loadAfter and loadBefore were provided. Only one can be provided.");
        }

        $body = [
            //'_source' => false,
            'query' => [
                'bool' => [
                    'must' => $must,
                    'must_not' => $mustNot,
                    'should' => $should,
                    'minimum_should_match' => count($should) > 0 ? 1 : 0,
                ],
            ],
            'sort' => [
                [
                    '@timestamp' => $loadBefore ? 'asc' : 'desc', // Top/newer posts are queried in ascending order
                    'guid' => $loadBefore ? 'asc' : 'desc', // Tie breaker
                ]
            ],
        ];

        if ($loadAfter || $loadBefore) {
            $body['search_after'] = $this->decodeSort($loadAfter ?: $loadBefore);
        }

        $query = [
            'index' => $this->getSearchIndexName(),
            'body' => $body,
            'size' => $limit + 1,
        ];

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $response = $this->esClient->request($prepared);

        // If paginating backwards (top/newer), reverse the array as we do an ascending sort
        $hits = $loadBefore ? array_reverse($response['hits']['hits']) : $response['hits']['hits'];

        // The 'load newer' will be first items sort key OR a new GUID if no posts are returned
        $loadBefore = isset($hits[0]) ? $this->encodeSort($hits[0]['sort']) : $this->encodeSort([time() * 1000, Guid::build()]);


        // We return +1 $limit, so if we have more than our limit returned, we know there is another pagr
        if (count($response['hits']['hits']) > $limit) {
            $hasMore = true;
        } else {
            $hasMore = false;
        }

        $i = 0;
        foreach ($hits as $hit) {
            $entity = $this->fetchActivity((int) $hit['_id']);
    
            if (!$entity) {
                continue;
            }

            // pass to reference before we yield to support with Cursor based pagination
            $loadAfter = $this->encodeSort($hit['sort']);

            yield $entity;

            // Dont provide more than the limit (we request + 1 to do pagination)
            if (++$i > $limit) {
                break;
            }
        }
    }

    /**
     * Returns the top posts count
     * @return iterable<Activity>
     */
    public function getTopCount(
        QueryOpts $queryOpts,
        string &$loadAfter = null,
        string &$loadBefore = null,
        bool &$hasMore = null
    ): int {
        $topAlgo = new TopV2();

        $prepared = $this->prepareElastic($queryOpts);

        $must = $prepared['must'];
        $mustNot = $prepared['mustNot'];
        $should = $prepared['should'];

        // Min 1 vote
        $must[] = [
            'range' => [
                'votes:up' => [
                    'gte' => 1,
                ]
            ]
        ];

        if ($loadAfter && $loadBefore) {
            throw new ServerErrorException("Two cursors, loadAfter and loadBefore were provided. Only one can be provided.");
        }

        $must[] = [
            'bool' => [
                'should' => $should,
                'minimum_should_match' => 1
            ]
        ];

        $body = [
            '_source' => false,
            'query' => [
                'bool' => [
                    'must' => $must,
                    'must_not' => $mustNot,
                ],
            ]
        ];

        $query = [
            'index' => $this->getSearchIndexName(),
            'body' => $body,
        ];

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        // Setup cursors
        $loadAfter = $loadBefore;
        $loadBefore = $this->encodeSort([time() * 1000, Guid::build()]);

        $response = $this->esClient->request($prepared);

        return $response['count'] ?? 0;
    }

    /**
     * Returns the top posts
     * @return iterable<Activity>
     */
    public function getTop(
        QueryOpts $queryOpts,
        string &$loadAfter = null,
        string &$loadBefore = null,
        bool &$hasMore = null
    ): iterable {
        $topAlgo = new TopV2();

        $prepared = $this->prepareElastic($queryOpts);

        $limit = $queryOpts->limit;
        $must = $prepared['must'];
        $mustNot = $prepared['mustNot'];
        $should = $prepared['should'];
        $functionScores = [...$topAlgo->getFunctionScores(), ...$prepared['functionScores']];

        // Min 1 vote
        $must[] = [
            'range' => [
                'votes:up' => [
                    'gte' => 1,
                ]
            ]
        ];

        // For search performance, only go back 90d
        $must[] = [
            'range' => [
                '@timestamp' => [
                    'gte' => "now-90d/d",
                ]
            ]
        ];

        if ($loadAfter && $loadBefore) {
            throw new ServerErrorException("Two cursors, loadAfter and loadBefore were provided. Only one can be provided.");
        }

        $must[] = [
            'bool' => [
                'should' => $should,
                'minimum_should_match' => 1
            ]
        ];

        $body = [
            '_source' => false,
            'query' => [
                'function_score' => [
                    'score_mode' => 'multiply',
                    'query' => [
                        'bool' => [
                            'must' => $must,
                            'must_not' => $mustNot,
                        ],
                    ],
                    'functions' => $functionScores,
                ]
            ],
            'sort' => [
                [
                    '_score' => [
                        'order' => $loadBefore ? 'asc' : 'desc', // Top/newer posts are queried in ascending order
                    ],
                    'guid' => $loadBefore ? 'asc' : 'desc', // Tie breaker
                ]
            ],
        ];


        if ($loadAfter || $loadBefore) {
            $body['search_after'] = $this->decodeSort($loadAfter ?: $loadBefore);
        }

        $query = [
            'index' => $this->getSearchIndexName(),
            'body' => $body,
            'size' => $limit + 1,
        ];

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $response = $this->esClient->request($prepared);

        // If paginating backwards (top/newer), reverse the array as we do an ascending sort
        $hits = $loadBefore ? array_reverse($response['hits']['hits']) : $response['hits']['hits'];

        // The 'load newer' will be first items sort key
        // For the 'top' query, as score change, we will have no 'load before' on the first request

        $loadBefore = isset($hits[0]) && $loadAfter ? $this->encodeSort($hits[0]['sort']) : null;

        // We return +1 $limit, so if we have more than our limit returned, we know there is another pagr
        if (count($response['hits']['hits']) > $limit) {
            $hasMore = true;
        } else {
            $hasMore = false;
        }

        $i = 0;
        foreach ($hits as $hit) {
            $entity = $this->fetchActivity((int) $hit['_id']);
    
            if (!$entity) {
                continue;
            }

            // pass to reference before we yield to support with Cursor based pagination
            $loadAfter = $this->encodeSort($hit['sort']);

            yield $entity;

            // Dont provide more than the limit (we request + 1 to do pagination)
            if (++$i >= $limit) {
                break;
            }
        }
    }

    /**
     * Returns the clustered recs for a user
     * NOTE: Will only support forward pagination at this time
     * @return iterable<Activity>
     */
    public function getClusteredRecs(
        User $user,
        int $limit = 12,
        string &$loadAfter = null,
        string &$loadBefore = null,
        bool &$hasMore = null
    ): iterable {
        if ($loadAfter && $loadBefore) {
            throw new ServerErrorException("Two cursors, loadAfter and loadBefore were provided. Only one can be provided.");
        }

        $offset = 0;
        $refFirstSeenTimestamp = time();

        if ($loadAfter || $loadBefore) {
            $cursorData = $this->decodeSort($loadAfter ?: $loadBefore);
            $offset = $cursorData[0];
            $refFirstSeenTimestamp = $cursorData[1];
        }

        $this->clusteredRecsRepository
            ->setUser($user);

        $result = $this->clusteredRecsRepository
            ->getList(
                clusterId: 0,
                limit: $limit + 1,
                demote: true,
                pseudoId: $this->seenManager->getIdentifier(),
                tags: [],
                offset: $offset,
                refFirstSeenTimestamp: $refFirstSeenTimestamp,
            );
        
        // Get all the results to aid with pagination
        $allResults = iterator_to_array($result);

        if (count($allResults) > $limit) {
            $hasMore = true;
        } else {
            $hasMore = false;
        }

        $i = 0;
        foreach ($allResults as $scoredGuid) {
            $entity = $this->fetchActivity((int) $scoredGuid->getGuid());
    
            if (!$entity) {
                continue;
            }

            $loadAfter = $this->encodeSort([
                ++$offset,
                $refFirstSeenTimestamp,
                $scoredGuid->getScore()
            ]);

            yield $entity;

            // Dont provide more than the limit (we request + 1 to do pagination)
            if (++$i > $limit) {
                break;
            }
        }
    }

    /**
     * Build bool query parts for elastic based queries
     * @return array
     */
    private function prepareElastic(QueryOpts $queryOpts): array
    {
        $must = [];
        $mustNot = [];
        $should = [];
        $functionScores = [];

        // Feeds should always return posts less than current time
        $must[] = [
            'range' => [
                '@timestamp' => [
                    'lte' => "now" // Never show posts that are in the future
                ]
            ]
        ];

        // Never show pending posts
        $mustNot[] = [
            'term' => [
                'pending' => true,
            ]
        ];

        // Never show soft deletes
        $mustNot[] = [
            'term' => [
                'deleted' => true,
            ]
        ];

        if ($queryOpts->onlyOwn) {
            // Return own posts only
            $must[] = [
                'term' => [
                    'owner_guid' => (string) $queryOpts->user->getGuid()
                ],
            ];
        }

        if ($queryOpts->onlySubscribed) {
            // Posts from subscriptions
            $should[] = [
                'terms' => [
                    'owner_guid' => [
                        'index' => 'minds-graph-subscriptions',
                        'id' => (string) $queryOpts->user->getGuid(),
                        'path' => 'guids',
                    ],
                ],
            ];

            // Return own posts too
            $should[] = [
                'term' => [
                    'owner_guid' => (string) $queryOpts->user->getGuid()
                ],
            ];
        }

        if ($queryOpts->onlyGroups) {
            // Posts from groups user is member of
            $must[] = [
                'terms' => [
                    'container_guid' =>
                        array_map(function ($guid) {
                            return (string) $guid;
                        }, $this->groupsMembershipManager->getGroupGuids($queryOpts->user)),
                ]
            ];
        }

        if ($queryOpts->query) {
            $words = explode(' ', $queryOpts->query);

            $multiMatch = [
                'multi_match' => [
                    'query' => $queryOpts->query,
                    'fields' => ['name^2', 'title^12', 'message^12', 'description^12', 'brief_description^8', 'username^8', 'tags^12', 'auto_caption^12'],
                ],
            ];

            if (count($words) > 1) {
                $multiMatch['multi_match']['type'] = 'phrase';
            }
            
            $this->experimentsManager
                ->setUser($queryOpts->user);

            if ($this->experimentsManager->isOn('engine-2619-inferred-tags')) {
                $multiMatch['multi_match']['fields'][] = 'inferred_tags^12';
            }

            $must[] = $multiMatch;
        }

        if ($queryOpts->accessId) {
            // Only public posts
            $must[] = [
                'terms' => [
                    'access_id' => [$queryOpts->accessId],
                ],
            ];
        }

        switch ($queryOpts->mediaTypeEnum) {
            case MediaTypeEnum::ALL:
                // noop
                break;
            case MediaTypeEnum::BLOG:
                $must[] = [
                    'exists' => [
                        'field' => 'entity_guid'
                    ]
                ];
                $mustNot[] = [
                    'terms' => [
                        'custom_type' => [ 'batch', 'video' ]
                    ]
                ];
                break;
            case MediaTypeEnum::IMAGE:
                $must[] = [
                    'term' => [
                        'custom_type' => 'batch'
                    ]
                ];
                break;
            case MediaTypeEnum::VIDEO:
                $must[] = [
                    'term' => [
                        'custom_type' => 'video'
                    ]
                ];
                break;
        }

        if ($queryOpts->seenEntitiesFilterStrategy !== SeenEntitiesFilterStrategyEnum::NOOP) {
            // Demote posts we've already seen
            $seenEntities = $this->seenManager->listSeenEntities();
            if (count($seenEntities) > 0) {
                switch ($queryOpts->seenEntitiesFilterStrategy) {
                    case SeenEntitiesFilterStrategyEnum::DEMOTE:
                        $functionScores[] = [
                            'filter' => [
                                'terms' => [
                                    'guid' => Text::buildArray($seenEntities),
                                ]
                            ],
                            'weight' => 0.01
                        ];
                        break;
                    case SeenEntitiesFilterStrategyEnum::EXCLUDE:
                        $mustNot[] = [
                            'terms' => [
                                'guid' => Text::buildArray($seenEntities),
                            ],
                        ];
                        break;
                }
            }
        }

        $nsfw = array_diff([1, 2, 3, 4, 5, 6], $queryOpts->nsfw);
        if ($nsfw) {
            $mustNot[] = [
                'terms' => [
                    'nsfw' => array_values($nsfw),
                ],
            ];

            if (in_array(6, $nsfw, false)) { // 6 is legacy 'mature'
                $mustNot[] = [
                    'term' => [
                        'mature' => true,
                    ],
                ];
            }
        }

        return [
            'must' => $must,
            'mustNot' => $mustNot,
            'should' => $should,
            'functionScores' => $functionScores,
        ];
    }

    /**
     * Encodes the sort to base64
     */
    protected function encodeSort(array $sort): string
    {
        return base64_encode(json_encode($sort));
    }

    /**
     * Decode the sort from base64
     */
    protected function decodeSort(string $sort): array
    {
        return json_decode(base64_decode($sort, true), true);
    }

    /**
     * Fetches activity from the entities builder and runs acl checks
     * @return Activity
     */
    private function fetchActivity(int $guid): ?Activity
    {
        $entity = $this->entitiesBuilder->single($guid);

        if (!$entity instanceof Activity) {
            return null;
        }

        return $this->acl->read($entity) ? $entity : null;
    }

    /**
     * The search index to query against
     */
    private function getSearchIndexName(): string
    {
        return 'minds-search-activity';
    }
}
