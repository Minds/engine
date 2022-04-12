<?php

namespace Minds\Core\Feeds\ClusteredRecommendations;

use Generator;
use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch\Client as ElasticSearchClient;
use Minds\Core\Data\ElasticSearch\Prepared\Search as PreparedSearch;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic\ScoredGuid;

/**
 * Repository class to fetch data from clustered recommendations index in ES
 */
class Repository
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
    public function getList(int $clusterId, int $limit): Generator
    {
        $preparedSearch = $this->buildQuery($clusterId, $limit);

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
    private function buildQuery(int $clusterId, int $limit): PreparedSearch
    {
        $query = [
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'term' => [
                                    'cluster_id' => $clusterId
                                ]
                            ]
                        ]
                    ]
                ],
                'sort' => [
                    'score' => 'desc'
                ]
            ],
            'index' => $this->index,
            'size' => $limit
        ];

        $preparedSearch = new PreparedSearch();
        $preparedSearch->query($query);

        return $preparedSearch;
    }
}
