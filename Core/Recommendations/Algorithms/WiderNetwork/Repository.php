<?php

namespace Minds\Core\Recommendations\Algorithms\WiderNetwork;

use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch\Client as ElasticSearchClient;
use Minds\Core\Data\ElasticSearch\Prepared\Search as PreparedSearchQuery;
use Minds\Core\Di\Di;
use Minds\Core\Recommendations\RepositoryInterface;
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
     * @return Response
     */
    public function getList(?array $options = null): Response
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
            'size' => 0,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $must,
                        'must_not' => $mustNot,
                    ],
                ],
                'aggs' => [
                    'subscriptions' => [
                        'terms' => [
                            'field' => 'entity_guid.keyword',
                            'size' => $limit,
                            'order' => [
                                '_count' =>  'desc',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $preparedQuery = new PreparedSearchQuery();
        $preparedQuery->query($query);
        return $preparedQuery;
    }

    /**
     * Processes the query response and prepares it in the format required for the 'getList' method to return
     * @param PreparedSearchQuery $preparedQuery
     * @return Response
     */
    private function prepareResponse(PreparedSearchQuery $preparedQuery): Response
    {
        $result = $this->elasticSearchClient->request($preparedQuery);

        $response = new Response();

        foreach ($result['aggregations']['subscriptions']['buckets'] as $row) {
            $response[] = (new Suggestion())
                ->setConfidenceScore($row['doc_count'])
                ->setEntityGuid($row["key"])
                ->setEntity(Factory::build($row["key"]))
                ->setEntityType('user');
        }

        return $response;
    }
}
