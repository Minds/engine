<?php

namespace Minds\Core\Recommendations\Algorithms\WiderNetwork;

use Generator;
use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch\Client as ElasticSearchClient;
use Minds\Core\Data\ElasticSearch\Prepared\Search as PreparedSearchQuery;
use Minds\Core\Di\Di;
use Minds\Core\Suggestions\Suggestion;
use Minds\Entities\Factory;

/**
 * Repository to interact with elasticsearch to fetch entries for the wider network recommendations algorithm
 */
class Repository implements RepositoryInterface
{
    public function __construct(
        private ?ElasticSearchClient $elasticSearchClient = null,
        private ?RepositoryOptions $options = null,
        private ?Config $config = null
    ) {
        $this->elasticSearchClient = $this->elasticSearchClient ?? Di::_()->get('Database\ElasticSearch');
        $this->options = $this->options ?? new RepositoryOptions();
        $this->config = $this->config ?? Di::_()->get("Config");
    }

    /**
     * Returns a list of entities
     * @param array|null $options
     * @return Generator|Suggestion[]
     */
    public function getList(?array $options = null): Generator
    {
        $this->options->init($options);

        $must = $this->prepareMustPartOfQuery();
        $mustNot = $this->prepareMustNotPartOfQuery();

        $preparedQuery = $this->prepareQuery(
            $this->options->getLimit(),
            $must,
            $mustNot
        );

        return $this->prepareResponse($preparedQuery);
    }

    /**
     * Prepares the 'must' part of the query to ElasticSearch
     * @return array
     */
    private function prepareMustPartOfQuery(): array
    {
        $must = [];

        $must[]['term'] = [
            "action" => "vote:up"
        ];

        $must[]['terms'] = [
            "entity_owner_guid" => [
                "index" => "minds-graph-subscriptions",
                "id" => $this->options->getUserGuid(),
                "path" => "guids"
            ]
        ];

        return $must;
    }

    private function prepareFunctionScores(): array
    {
        return [
            [
                'filter' => [
                    'range' => [
                        '@timestamp' => [
                            'gte' => strtotime('-12 hours'),
                        ]
                    ],
                ],
                'weight' => 4,
            ],
            [
                'filter' => [
                    'range' => [
                        '@timestamp' => [
                            'lt' => strtotime('-12 hours'),
                            'gte' => strtotime('-36 hours'),
                        ]
                    ],
                ],
                'weight' => 2,
            ],
            // [
            //     'gauss' => [
            //         '@timestamp' => [
            //             'offset' => '12h', // Do not decay until we reach this bound
            //             'scale' => '24h', // Peak decay will be here
            //             'decay' => 0.9
            //         ],
            //     ],
            //     'weight' => 20,
            // ]
        ];
    }

    private function prepareSort(): array
    {
        return [
            '_score' => [
                'order' => 'desc'
            ]
        ];
    }

    /**
     * Prepares the 'must' part of the query to ElasticSearch
     * @return array
     */
    private function prepareMustNotPartOfQuery(): array
    {
        $mustNot = [];

        // Remove Minds channel
        $mustNot[]['term'] = [
            'entity_owner_guid' => $this->config->get("default_recommendations_user"),
        ];

        return $mustNot;
    }

    /**
     * Generates the prepared query putting together all the different parts of the query.
     * @param int $limit
     * @param array $must
     * @param array $mustNot
     * @return PreparedSearchQuery
     */
    private function prepareQuery(int $limit, array $must, array $mustNot): PreparedSearchQuery
    {
        $query = [
            'index' => 'minds-metrics-*',
            'body' => [
                'query' => [
                    'function_score' => [
                        'query' => [
                            'bool' => [
                                'must' => $must,
                                'must_not' => $mustNot,
                            ],
                        ],
                        'functions' => $this->prepareFunctionScores()
                    ]
                ],
                'sort' => [
                    $this->prepareSort()
                ]
            ],
        ];

        $preparedQuery = new PreparedSearchQuery();
        $preparedQuery->query($query);
        return $preparedQuery;
    }

    /**
     * Processes the query response and prepares it in the format required for the 'getList' method to return
     * @param PreparedSearchQuery $preparedQuery
     * @return Generator|Suggestion[]
     */
    private function prepareResponse(PreparedSearchQuery $preparedQuery): Generator
    {
        $result = $this->elasticSearchClient->request($preparedQuery);

        foreach ($result['hits']['hits'] as $row) {
            $entry = $row['_source'];
            yield (new Suggestion())
                ->setConfidenceScore($row['_score'])
                ->setEntityGuid($entry['entity_guid'])
                ->setEntity(Factory::build($entry["entity_guid"]))
                ->setEntityType('activity');
        }
    }
}
