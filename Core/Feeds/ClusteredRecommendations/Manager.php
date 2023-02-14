<?php

namespace Minds\Core\Feeds\ClusteredRecommendations;

use Exception;
use Generator;
use Minds\Common\Repository\Response;
use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Feeds\Elastic\ScoredGuid;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Feeds\Seen\Manager as SeenManager;
use Minds\Core\Recommendations\UserRecommendationsCluster;
use Minds\Entities\User;

/**
 *  Manager class to handle clustered recommendations feed's logic
 */
class Manager
{
    private User $user;

    public function __construct(
        private ?RepositoryInterface $repository = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?UserRecommendationsCluster $userRecommendationsCluster = null,
        private ?SeenManager $seenManager = null,
        private ?RepositoryFactory $repositoryFactory = null,
        private ?ExperimentsManager $experimentsManager = null
    ) {
        $this->entitiesBuilder ??= new EntitiesBuilder();
        $this->userRecommendationsCluster ??= new UserRecommendationsCluster();
        $this->seenManager = $seenManager ?? Di::_()->get('Feeds\Seen\Manager');
        $this->repositoryFactory ??= new RepositoryFactory();
        $this->experimentsManager ??= Di::_()->get("Experiments\Manager");
    }

    /**
     * Get the correct repository based on the
     * @return RepositoryInterface
     */
    private function getRepository(): RepositoryInterface
    {
        $this->experimentsManager->setUser($this->user);
        return match ($this->experimentsManager->isOn('engine-2494-clustered-recs-v2')) {
            true => $this->repositoryFactory->getInstance(MySQLRepository::class),
            default => $this->repositoryFactory->getInstance(LegacyMySQLRepository::class)
        };
    }

    /**
     * Sets the user
     * @param User $user
     * @return $this
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Gets the list of entities based on the clustered recommendations ES index
     * @param int $limit
     * @param bool $unseen
     * @return Response
     * @throws Exception
     */
    public function getList(int $limit, bool $unseen = false): Response
    {
        $this->repository ??= $this->getRepository();

        $clusterId = 0;
        if (!$this->experimentsManager->isOn('engine-2494-clustered-recs-v2')) {
            $clusterId = $this->userRecommendationsCluster->calculateUserRecommendationsClusterId($this->user);
        } else {
            $this->repository->setUser($this->user);
        }

        $seenEntitiesList = [];

        $entries = $this->repository->getList($clusterId, $limit, $seenEntitiesList, $unseen, $this->seenManager->getIdentifier());
        $preparedEntities = $this->prepareEntities($entries);

        $paginationToken = $this->getPaginationToken($preparedEntities);

        $response = new Response($preparedEntities);
        $response->setPagingToken($paginationToken ?: '');

        return $response;
    }

    /**
     * Parses response from repository and return an array of FeedSyncEntities
     * @param ScoredGuid $recommendation
     * @return FeedSyncEntity
     * @throws Exception
     */
    private function prepareFeedSyncEntity(ScoredGuid $recommendation): FeedSyncEntity
    {
        $ownerGuid = $recommendation->getOwnerGuid() ?: $recommendation->getGuid();
        $entityType = $recommendation->getType() ?? 'entity';

        $urn = implode(':', [
            'urn',
            $entityType ?: 'entity',
            $recommendation->getGuid()
        ]);

        return (new FeedSyncEntity())
            ->setGuid((string) $recommendation->getGuid())
            ->setOwnerGuid((string) $ownerGuid)
            ->setUrn(new Urn($urn))
            ->setTimestamp($recommendation->getTimestamp());
    }

    /**
     * Prepares final array, hydrating the top 12 entities
     * @param Generator $recs
     * @return array
     * @throws Exception
     */
    private function prepareEntities(Generator $recs): array
    {
        $entities = [];

        foreach ($recs as $rec) {
            $feedSyncEntity = $this->prepareFeedSyncEntity($rec);

            if (count($entities) < 12) {
                if ($entity = $this->entitiesBuilder->single($feedSyncEntity->getGuid())) {
                    $entities[] = (new FeedSyncEntity)
                        ->setGuid($entity->getGuid())
                        ->setOwnerGuid($entity->getOwnerGuid())
                        ->setUrn($entity->getUrn())
                        ->setEntity($entity);
                }
                continue;
            }

            $entities[] = $feedSyncEntity;
        }

        return $entities;
    }

    /**
     * Gets the pagination token to return with the list of entities so that the FE knows if it should fetch more rows
     * @param array $feedSyncEntities
     * @return string
     */
    private function getPaginationToken(array $feedSyncEntities): string
    {
        return (string) (
            array_reduce(
                $feedSyncEntities,
                function ($carry, FeedSyncEntity $feedSyncEntity) {
                    return min($feedSyncEntity->getTimestamp() ?: INF, $carry);
                },
                INF
            ) - 1
        );
    }
}
