<?php

namespace Minds\Core\Discovery\Repositories;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Common\Urn;
use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch\Client as ElasticSearchClient;
use Minds\Core\Data\ElasticSearch\Prepared\Search as PreparedSearchQuery;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Suggestions\Suggestion;
use Minds\Entities\ElasticSearchRepositoryOptions;

class EntitiesRepository
{
    private string $index = "";

    public function __construct(
        private ?ElasticSearchClient $elasticSearchClient = null,
        private ?ElasticSearchRepositoryOptions $options = null,
        private ?Config $config = null
    ) {
        $this->elasticSearchClient = $this->elasticSearchClient ?? Di::_()->get('Database\ElasticSearch');
        $this->options ??= new ElasticSearchRepositoryOptions();
        $this->config = $this->config ?? Di::_()->get("Config");
        $this->index = $this->config->get('elasticsearch')['indexes']['search_prefix'];
    }

    /**
     * @param Suggestion[] $entities
     * @param array|null $options
     * @return Response
     * @throws Exception
     */
    public function getWiderNetworkEntitiesList(array $entities, ?array $options = null): Response
    {
        $this->options->init($options);

        $query = $this->prepareQuery($entities);
        $results = $this->elasticSearchClient->request($query);

        return $this->prepareResponse($results['hits']['hits']);
    }

    /**
     * @param Suggestion[] $entities
     * @return PreparedSearchQuery
     */
    private function prepareQuery(array $entities): PreparedSearchQuery
    {
        $query = [];

        $this->setQueryIndexes($query);
        $this->setQuerySize($query);
        $this->setQueryBody($query, $entities);

        $preparedQuery = new PreparedSearchQuery();
        $preparedQuery->query($query);
        return $preparedQuery;
    }

    private function setQueryIndexes(array &$query): void
    {
        $query['index'] = implode(',', array_map(function ($type) {
            return $this->index . '-' . $type;
        }, [
            'activity',
            'object-image',
            'object-video',
            'object-blog',
        ]));
    }

    private function setQuerySize(array &$query): void
    {
        $query['size'] = $this->options->getLimit();
        $query['from'] = $this->options->getOffset();
    }

    /**
     * @param array $query
     * @param Suggestion[] $entities
     * @return void
     */
    private function setQueryBody(array &$query, array $entities): void
    {
        $query['body'] = [
            'sort' => $this->prepareSort(),
            'query' => $this->prepareQueryMustSection($entities),
        ];
    }

    private function prepareFunctionScores(): array
    {
        return [
            [
                'field_value_factor' => [
                    'field' => 'votes:up',
                    'factor' => 1,
                    'modifier' => 'sqrt',
                    'missing' => 0,
                ],
            ],
            [
                'filter' => [
                    'range' => [
                        '@timestamp' => [
                            'gte' => 'now-12h',
                        ]
                    ],
                ],
                'weight' => 4,
            ],
            [
                'filter' => [
                    'range' => [
                        '@timestamp' => [
                            'lt' => 'now-12h',
                            'gte' => 'now-36h',
                        ]
                    ],
                ],
                'weight' => 2,
            ],
            [
                'gauss' => [
                    '@timestamp' => [
                        'offset' => '12h', // Do not decay until we reach this bound
                        'scale' => '24h', // Peak decay will be here
                        'decay' => 0.9
                    ],
                ],
                'weight' => 20,
            ]
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
     * Prepares the 'must' section of the query's body
     * @param Suggestion[]|null $entities
     * @return array
     */
    private function prepareQueryMustSection(?array $entities): array
    {
        $must = [];

        $must['terms'] = [
            "guid" => array_map(function (Suggestion $item): string {
                return $item->getEntityGuid();
            }, $entities)
        ];

        return $must;
    }

    /**
     * @param array $entities
     * @return Response
     * @throws Exception
     */
    private function prepareResponse(array $entities): Response
    {
        $feedSyncEntities = [];
        $guids = [];
        foreach ($entities as $entity) {
            $entityDetails = $entity['_source'];
            $guid = $entityDetails['guid'];

            if (isset($guids[$guid])) {
                continue;
            }

            $guids[$guid] = true;

            $type = $entityDetails['type'] ?? 'entity';
            if (str_starts_with($type, 'object-')) {
                $type = str_replace('object-', '', $type);
            }

            $urn = implode(':', [
                'urn',
                $type,
                $guid,
            ]);

            $feedSyncEntities[] = (new FeedSyncEntity())
                ->setGuid($guid)
                ->setOwnerGuid($entityDetails['owner_guid'])
                ->setUrn(new Urn($urn))
                ->setTimestamp($entityDetails['@timestamp']);
        }

        $results = [];
        $next = '';
        if (count($feedSyncEntities) > 0) {
            $entitiesBuilder = new EntitiesBuilder();

            $next = (string)(array_reduce($feedSyncEntities, function ($carry, FeedSyncEntity $feedSyncEntity) {
                return min($feedSyncEntity->getTimestamp() ?: INF, $carry);
            }, INF) - 1);

            $hydrateGuids = array_map(function (FeedSyncEntity $feedSyncEntity) {
                return $feedSyncEntity->getGuid();
            }, array_slice($feedSyncEntities, 0, 12)); // hydrate the first 12

            $hydratedEntities = $entitiesBuilder->get(['guids' => $hydrateGuids]);

            foreach ($hydratedEntities as $entity) {
                $entry = new FeedSyncEntity();
                $entry->setGuid($entity->getGuid());
                $entry->setOwnerGuid($entity->getOwnerGuid());
                $entry->setUrn($entity->getUrn());
                $entry->setEntity($entity);
                $results[] = $entry;
            }

            // TODO: Optimize this
            foreach (array_slice($feedSyncEntities, 12) as $entity) {
                $results[] = $entity;
            }
        }

        return (new Response($results))->setPagingToken($next ?: '');
    }
}
