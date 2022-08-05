<?php

namespace Minds\Core\Feeds\ClusteredRecommendations;

use Generator;
use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch\Client as ElasticSearchClient;
use Minds\Core\Data\ElasticSearch\Prepared\Search as PreparedSearch;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic\ScoredGuid;

/**
 * ElasticSearchRepository class to fetch data from clustered recommendations index in ES
 */
class ElasticSearchRepository implements RepositoryInterface
{
    private string $index;

    public function __construct(
        private ?ElasticSearchClient $elasticSearchClient = null,
        private ?Config $config = null
    ) {
        $this->elasticSearchClient ??= Di::_()->get("Database\ElasticSearch");
        $this->config ??= Di::_()->get("Config");

        $this->index = $this->config?->get("elasticsearch")['indexes']['clustered_entities'];
    }

    /**
     * Runs query and yields results
     * @param int $clusterId
     * @param int $limit
     * @return Generator
     */
    public function getList(int $clusterId, int $limit, array $exclude = [], bool $demote = false, ?string $pseudoId = null): Generator
    {
        $preparedSearch = $this->buildQuery($clusterId, $limit, $exclude, $demote);
        $results = $this->elasticSearchClient->request($preparedSearch);

        foreach ($results['hits']['hits'] as $doc) {
            $source = $doc['_source'];
            yield (new ScoredGuid())
                ->setGuid($source['entity_guid'])
                ->setType('activity')
                ->setScore($source['score'])
                ->setOwnerGuid($source['entity_owner_guid'])
                ->setTimestamp($source['@time_created']);
        }
    }

    /**
     * Prepares ES search query
     * @param int $clusterId
     * @param int $limit
     * @return PreparedSearch
     */
    private function buildQuery(int $clusterId, int $limit, array $exclude = [], bool $demote = false): PreparedSearch
    {
        $query = [
            'body' => [
                'query' => [
                    'function_score' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    [
                                        'term' => [
                                            'cluster_id' => $clusterId
                                        ]
                                    ]
                                ],
                                'must_not' => [
                                    [
                                        'terms' => [
                                            'entity_guid' => $exclude
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'score_mode' => 'multiply',
                        'functions' => [
                            [
                                'field_value_factor' => [
                                    'field' => 'score',
                                    'factor' => 1,
                                    'modifier' => 'log1p',
                                    'missing' => 0,
                                ],
                            ],
                            [
                                'gauss' => [
                                    '@time_created' => [
                                        'offset' => '24h', // Do not decay until we reach this bound
                                        'scale' => '7d', // Peak decay will be here
                                        'decay' => 0.5
                                    ],
                                ],
                            ],
                        ]
                    
                    ],
                ],
                'sort' => [
                    '_score' => 'desc'
                ]
            ],
            'index' => $this->index,
            'size' => $limit
        ];

        if ($exclude && $demote) {
            $query['body']['query']['function_score']['functions'][] = [
                'filter' => [
                    'terms' => [
                        'guid' => $exclude
                    ]
                ],
                'weight' => $this->config->get('seen-entities-weight') ?? 0.01
            ];
        }

        $preparedSearch = new PreparedSearch();
        $preparedSearch->query($query);

        return $preparedSearch;
    }
}
