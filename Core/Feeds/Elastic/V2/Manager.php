<?php
namespace Minds\Core\Feeds\Elastic\V2;

use Minds\Entities\User;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Guid;
use Minds\Core\Search\SortingAlgorithms\TopV2;
use Minds\Core\Feeds\ClusteredRecommendations;
use Minds\Entities\Activity;
use Minds\Exceptions\ServerErrorException;

class Manager
{
    public function __construct(
        protected ElasticSearch\Client $esClient,
        protected ClusteredRecommendations\Manager $clusteredRecsManager,
        protected EntitiesBuilder $entitiesBuilder,
    ) {
    }

    /**
     * Returns the latest subscribed posts
     * @return iterable<Activity>
     */
    public function getLatestSubscribed(
        User $user,
        int $limit = 12,
        string &$loadAfter = null,
        string &$loadBefore = null,
        bool &$hasMore = null
    ): iterable {
        $must = [];
        $should = [];

        $must[] = [
            'range' => [
                '@timestamp' => [
                    'lte' => time() * 1000, // Never show posts that are in the future
                ]
            ]
        ];

        if ($loadAfter && $loadBefore) {
            throw new ServerErrorException("Two cursors, loadAfter and loadBefore were provided. Only one can be provided.");
        }

        // Posts from subscriptions
        $should[] = [
            'terms' => [
                'owner_guid' => [
                    'index' => 'minds-graph-subscriptions',
                    'id' => (string) $user->getGuid(),
                    'path' => 'guids',
                ],
            ],
        ];

        // Return own posts too
        $should[] = [
            'term' => [
                'owner_guid' => (string) $user->getGuid()
            ],
        ];

        $body = [
            '_source' => false,
            'query' => [
                'bool' => [
                    'must' => $must,
                    'should' => $should,
                ],
            ],
            'sort' => [
                [
                    'guid' => $loadBefore ? 'asc' : 'desc', // Top/newer posts are queried in ascending order
                ]
            ],
        ];

        if ($loadAfter || $loadBefore) {
            $body['search_after'] = $this->decodeSort($loadAfter ?: $loadBefore);
        }

        $query = [
            'index' => 'minds-search-activity',
            'body' => $body,
            'size' => $limit + 1,
        ];

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $response = $this->esClient->request($prepared);

        // If paginating backwards (top/newer), reverse the array as we do an ascending sort
        $hits = $loadBefore ? array_reverse($response['hits']['hits']) : $response['hits']['hits'];

        // The 'load newer' will be first items sort key OR a new GUID if no posts are returned
        $loadBefore = isset($hits[0]) ? $this->encodeSort($hits[0]['sort']) : $this->encodeSort([Guid::build()]);


        // We return +1 $limit, so if we have more than our limit returned, we know there is another pagr
        if (count($response['hits']['hits']) > $limit) {
            $hasMore = true;
        } else {
            $hasMore = false;
        }

        $i = 0;
        foreach ($hits as $hit) {
            $entity = $this->entitiesBuilder->single($hit['_id']);
    
            if (!$entity instanceof Activity) {
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
     * Returns the top subscribed posts
     * @return iterable<Activity>
     */
    public function getTopSubscribed(
        User $user,
        int $limit = 12,
        string &$loadAfter = null,
        string &$loadBefore = null,
        bool &$hasMore = null
    ): iterable {
        $must = [];
        $should = [];

        $must[] = [
            'range' => [
                'votes:up' => [
                    'gte' => 1,
                ]
            ]
        ];

        $must[] = [
            'range' => [
                '@timestamp' => [
                    'lte' => time() * 1000, // Never show posts that are in the future
                ]
            ]
        ];

        if ($loadAfter && $loadBefore) {
            throw new ServerErrorException("Two cursors, loadAfter and loadBefore were provided. Only one can be provided.");
        }

        // Posts from subscriptions
        $should[] = [
            'terms' => [
                'owner_guid' => [
                    'index' => 'minds-graph-subscriptions',
                    'id' => (string) $user->getGuid(),
                    'path' => 'guids',
                ],
            ],
        ];

        // Return own posts too
        $should[] = [
            'term' => [
                'owner_guid' => (string) $user->getGuid()
            ],
        ];

        $must[] = [
            'bool' => [
                'should' => $should,
                'minimum_should_match' => 1
            ]
        ];

        $topAlgo = new TopV2();

        $body = [
            '_source' => false,
            'query' => [
                'function_score' => [
                    'score_mode' => 'multiply',
                    'query' => [
                        'bool' => [
                            'must' => $must,
                        ],
                    ],
                    'functions' => $topAlgo->getFunctionScores()
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
            'index' => 'minds-search-activity',
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
            $entity = $this->entitiesBuilder->single($hit['_id']);
    
            if (!$entity instanceof Activity) {
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
     * Returns the latest subscribed posts
     * @return iterable<Activity>
     */
    public function getClustedRecs(
        User $user,
        int $limit = 12,
        string &$loadAfter = null,
        string &$loadBefore = null,
        bool &$hasMore = null
    ): iterable {
        $result = $this->clusteredRecsManager->setUser($user)->getList(limit: $limit, unseen: true);
        
        foreach ($result as $scoredGuid) {
            $entity = $this->entitiesBuilder->single($scoredGuid->getGuid());
    
            if (!$entity instanceof Activity) {
                continue;
            }

            yield $entity;
        }
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
}
