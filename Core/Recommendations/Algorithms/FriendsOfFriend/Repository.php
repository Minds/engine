<?php

namespace Minds\Core\Recommendations\Algorithms\FriendsOfFriend;

use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch\Client as ElasticSearchClient;
use Minds\Core\Data\ElasticSearch\Prepared\Search as PreparedSearchQuery;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Recommendations\Algorithms\FriendsOfFriends\Validators\RepositoryOptionsValidator;
use Minds\Core\Recommendations\RepositoryInterface;
use Minds\Core\Suggestions\Suggestion;
use Minds\Entities\Factory;
use Minds\Exceptions\UserErrorException;
use Minds\Core\Security\Block;

/**
 * Responsible for fetching the relevant entities from ElasticSearch for the FriendsOfFriends algorithm
 */
class Repository implements RepositoryInterface
{
    public function __construct(
        private ?ElasticSearchClient $elasticSearchClient = null,
        private ?RepositoryOptions $options = null,
        private ?Config $config = null,
        private ?RepositoryOptionsValidator $optionsValidator = null,
        private ?Block\Manager $blockManager = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
    ) {
        $this->elasticSearchClient ??= Di::_()->get('Database\ElasticSearch');
        $this->options ??= new RepositoryOptions();
        $this->config ??= Di::_()->get('Config');
        $this->optionsValidator ??= new RepositoryOptionsValidator();
        $this->blockManager ??= Di::_()->get('Security\Block\Manager');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
    }

    /**
     * Sets the repository options using the provided array and validates them.
     * @throws UserErrorException
     */
    private function setOptions(?array $options): void
    {
        $this->options->init($options);
        $this->validateOptions();
    }

    /**
     * Performs validation of the
     * @throws UserErrorException
     */
    private function validateOptions(): void
    {
        if (!$this->optionsValidator->validate($this->options->toArray())) {
            throw new UserErrorException(
                "Some validation errors were encountered.",
                400,
                $this->optionsValidator->getErrors()
            );
        }
    }

    /**
     * Returns a list of entities
     * @param array|null $options
     * @return Response
     * @throws UserErrorException
     */
    public function getList(?array $options = null): Response
    {
        $this->setOptions($options);

        $query = $this->prepareQuery();
        return $this->prepareResponse($query);
    }

    /**
     * Prepares the 'must' part of the query to ElasticSearch
     * @return array
     */
    private function prepareQueryMustSection(): array
    {
        $must = [];

        // action that the loggedin user's subscriptions have performed
        $must[]['term'] = [
            "action" => "subscribe"
        ];

        $must[]['range'] = [
            '@timestamp' => [
                'gte' => strtotime("-90 days") * 1000
            ]
        ];

        // Include most recent logged-in user's subscription
        // as it might not be already available in the graph subscriptions index
        $must[]['term'] = [
            "user_guid" => $this->options->getCurrentChannelUserGuid()
        ];

        return $must;
    }

    /**
     * Prepares the 'should' part of the query to ElasticSearch
     * @return array
     */
    private function prepareQueryShouldSection(): array
    {
        $should = [
            "bool" => [
                "must" => []
            ]
        ];

        return $should;
    }

    /**
     * Prepares the 'must_not' part of the query to ElasticSearch
     * @return array
     */
    private function prepareQueryMustNotSection(): array
    {
        $mustNot = [];

        // excluding the logged-in user from the list of subscriptions
        // as we don't want to see the logged-in user in the  list of recommendations
        $mustNot[]['term'] = [
            "entity_guid" => $this->options->getTargetUserGuid()
        ];

        if (!empty($this->options->getCurrentChannelUserGuid())) {
            // excluding the most recent logged-in user's subscription from the list of subscriptions
            // as it might not be already available in the graph subscriptions index,
            // and we don't want to see it in the  list of recommendations
            $mustNot[]['term'] = [
                "entity_guid" => $this->options->getCurrentChannelUserGuid()
            ];
        }

        if (!empty($this->options->getMostRecentSubscriptions())) {
            // excluding the most recent subscriptions from the list of subscriptions
            // as they might not be already available in the graph subscriptions index,
            // and we don't want to see it in the  list of recommendations
            $mustNot[]['terms'] = [
                "entity_guid" => $this->options->getMostRecentSubscriptions()
            ];
        }

        // excluding logged-in user's subscriptions
        // from the list of recommendations
        $mustNot[]['terms'] = [
            "entity_guid" => [
                "index" => "minds-graph-subscriptions",
                "id" => $this->options->getTargetUserGuid(),
                "path" => "guids"
            ]
        ];

        return $mustNot;
    }

    private function prepareQuery(): PreparedSearchQuery
    {
        $query = [
            'index' => 'minds-metrics-*',
            'size' => 0,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $this->prepareQueryMustSection(),
                        'should' => $this->prepareQueryShouldSection(),
                        'must_not' => $this->prepareQueryMustNotSection(),
                    ],
                ],
                'aggs' => [
                    'channels' => [
                        'terms' => [
                            'field' => 'entity_guid.keyword',
                            'size' => $this->options->getLimit(),
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
        $count = 0;

        foreach ($result['aggregations']['channels']['buckets'] as $i => $row) {
            $entity = null;

            $blockEntry = (new Block\BlockEntry())
                ->setActorGuid($this->options->getTargetUserGuid())
                ->setSubjectGuid($row['key']);

            if ($this->blockManager->hasBlocked($blockEntry) || $this->blockManager->isBlocked($blockEntry)) {
                continue;
            }

            if (++$count <= 12) {
                $entity = $this->entitiesBuilder->single($row['key']);
            }

            $response[] = (new Suggestion())
                ->setConfidenceScore($row['doc_count'])
                ->setEntityGuid($row["key"])
                ->setEntity($entity)
                ->setEntityType('user');
        }

        return $response;
    }
}
