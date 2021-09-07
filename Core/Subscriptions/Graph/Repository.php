<?php
namespace Minds\Core\Subscriptions\Graph;

use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\FeedSyncEntity;

/**
 * Subscriptions Graph Repository
 * @package Minds\Core\Subscriptions\Graph
 */
class Repository
{
    /** @var Client */
    protected $es;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Config */
    protected $config;

    /**
     * Repository constructor.
     * @param $es
     */
    public function __construct(
        $es = null,
        EntitiesBuilder $entitiesBuilder = null,
        Config $config = null
    ) {
        $this->es = $es ?: Di::_()->get('Database\ElasticSearch');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * @param RepositoryGetOptions $options
     * @return Response
     */
    public function getSubscriptions(RepositoryGetOptions $options): Response
    {
        $userIndex = $this->config->get('elasticsearch')['indexes']['search_prefix'] . '-user';
        $query = [
            'index' => $userIndex,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'terms' => [
                                    'guid' => [
                                        'index' => 'minds-graph-subscriptions',
                                        'id' => $options->getUserGuid(),
                                        'path' => 'guids',
                                    ],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if ($options->getSearchQuery()) {
            $query['body']['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $options->getSearchQuery(),
                    'operator' => 'OR',
                    'fields' => ['username^10', 'name^5', 'brief_description'],
                ],
            ];
        }

        $query['size'] = (int) $options->getLimit();
        $query['from'] = (int) $options->getOffset();

        $prepared = new Search();
        $prepared->query($query);

        $result = $this->es->request($prepared);

        $response = new Response($result['hits']['hits']);
        $response->setPagingToken($query['from'] + $query['size']);
        $response->setLastPage($response->count() < $query['size']);

        return $response->map(function ($document) {
            $feedSyncEntity = new FeedSyncEntity();

            $feedSyncEntity
                ->setGuid($document['_source']['guid'])
                ->setOwnerGuid($document['_source']['guid'])
                ->setTimestamp($document['_source']['time_created'])
                ->setUrn("urn:user:{$document['_source']['guid']}");

            return $feedSyncEntity;
        });
    }

    /**
     * @param RepositoryGetOptions $options
     * @return Response
     */
    public function getSubscribers(RepositoryGetOptions $options): Response
    {
        $query = [
            'index' => 'minds-metrics-*',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'term' => [
                                    'action' => 'subscribe'
                                ]
                            ],
                            [
                                'term' => [
                                    'entity_guid.keyword' => $options->getUserGuid(),
                                ]
                            ],
                            [
                                'range' => [
                                    '@timestamp' => [
                                        'gte' => strtotime('30 days ago') * 1000
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'sort' => [
                    [
                        '@timestamp' => [
                            'order' => 'desc'
                        ],
                    ]
                ]
            ]
        ];

        $query['size'] = (int) $options->getLimit();
        $query['from'] = (int) $options->getOffset();

        $prepared = new Search();
        $prepared->query($query);

        $result = $this->es->request($prepared);

        $response = new Response($result['hits']['hits']);
        $response->setPagingToken($query['from'] + $query['size']);
        $response->setLastPage($response->count() < $query['size']);

        return $response->map(function ($document) {
            $entity = $this->entitiesBuilder->single($document['_source']['user_guid']);
            return $entity;
        });
    }
}
