<?php

namespace Minds\Core\Recommendations;

use Minds\Common\Repository\Response;
use Minds\Core\Data\ElasticSearch\Client as ElasticSearchClient;
use Minds\Core\Data\ElasticSearch\Prepared\Search as PreparedSearchQuery;
use Minds\Core\Di\Di;
use Minds\Core\Suggestions\Suggestion;

class Repository implements RepositoryInterface
{
    public function __construct(
        private ?ElasticSearchClient $elasticSearchClient = null,
        private ?RepositoryOptions $options = null
    ) {
        $this->elasticSearchClient = $this->elasticSearchClient ?? Di::_()->get('Database\ElasticSearch');
        $this->options = $this->options ?? new RepositoryOptions();
    }

    public function getList(?RepositoryOptions $options = null): Response
    {
        $must = $this->prepareMustPartOfQuery();
        $mustNot = $this->prepareMustNotPartOfQuery();

        $preparedQuery = $this->prepareQuery(
            $this->options->getLimit(),
            $must,
            $mustNot
        );

        return $this->prepareResponse($preparedQuery);
    }

    private function prepareMustPartOfQuery(): array
    {
        $must = [];

        $must[]['term'] = [
            "action.keyword" => "vote:up"
        ];

        $must[]['terms'] = [
            "entity_guid.keyword" => [
                "index" => "minds-graph-subscriptions",
                "id" => $this->options->getUserGuid(),
                "path" => "guids"
            ]
        ];

        return $must;
    }

    private function prepareMustNotPartOfQuery(): array
    {
        $mustNot = [];

        // Remove Minds channel
        $mustNot[]['term'] = [
            'user_guid.keyword' => '100000000000000519',
        ];

        return $mustNot;
    }

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

    private function prepareResponse(PreparedSearchQuery $preparedQuery): Response
    {
        $result = $this->elasticSearchClient->request($preparedQuery);

        $response = new Response();

        foreach ($result['aggregations']['subscriptions']['buckets'] as $row) {
            $response[] = (new Suggestion())
                ->setConfidenceScore($row['doc_count'])
                ->setEntityGuid($row['key'])
                ->setEntityType('user');
        }

        return $response;
    }
}
