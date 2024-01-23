<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\MultiTenant\Enums\FeaturedEntityTypeEnum;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\Repositories\FeaturedEntitiesRepository;
use Minds\Core\MultiTenant\Types\FeaturedEntity;
use Minds\Core\MultiTenant\Types\FeaturedEntityConnection;
use Minds\Core\MultiTenant\Types\FeaturedEntityEdge;
use Minds\Core\MultiTenant\Types\FeaturedUser;

/**
 * Service for featured entities.
 */
class FeaturedEntityService
{
    public function __construct(
        private FeaturedEntitiesRepository $repository,
        private Config $config
    ) {
    }

    /**
     * Gets featured entities.
     * @param FeaturedEntityTypeEnum $type - type of featured entities.
     * @param int $loadAfter - load after cursor.
     * @param int $limit - limit of entities to load.
     * @param ?int $tenantId - id of the tenant.
     * @return FeaturedEntityConnection - featured entities connection.
     */
    public function getFeaturedEntities(
        FeaturedEntityTypeEnum $type,
        int $loadAfter = 0,
        int $limit = 12,
        ?int $tenantId = null
    ): FeaturedEntityConnection {
        if (!$tenantId) {
            $tenantId = $this->config->get('tenant_id');
        }

        $hasMore = false;
        $entities = $this->repository->getFeaturedEntities(
            tenantId: $tenantId,
            type: $type,
            limit: $limit,
            loadAfter: $loadAfter,
            hasMore: $hasMore,
        );

        $edges = $this->buildEdges($entities, (string) $loadAfter);

        return (new FeaturedEntityConnection())
            ->setEdges($edges)
            ->setPageInfo(new PageInfo(
                hasNextPage: $hasMore,
                hasPreviousPage: false, // not supported.
                startCursor: (string) $loadAfter,
                endCursor: (string) ($limit + $loadAfter),
            ));
    }

    /**
     * Stores featured entity.
     * @param FeaturedEntity $featuredEntity - featured entity to store.
     * @return FeaturedEntity - stored featured entity.
     */
    public function storeFeaturedEntity(FeaturedEntity $featuredEntity): FeaturedEntity
    {
        return $this->repository->upsertFeaturedEntity($featuredEntity);
    }

    /**
     * Deletes featured entity.
     * @param integer $entityGuid - guid of featured entity to delete.
     * @param integer|null $tenantId - id of the tenant.
     * @return bool - true if featured entity was deleted, false otherwise.
     */
    public function deleteFeaturedEntity(int $entityGuid, ?int $tenantId = null): bool
    {
        if (!$tenantId) {
            $tenantId = $this->config->get('tenant_id');
        }

        return $this->repository->deleteFeaturedEntity(
            tenantId: $tenantId,
            entityGuid: $entityGuid
        );
    }

    /**
     * Builds edges for connection from generator of featured entities.
     * @param iterable $entities - generator of featured entities.
     * @param string $cursor - cursor used to load these entities.
     * @return array - array of edges.
     */
    private function buildEdges(iterable $entities, string $cursor = ''): array
    {
        $edges = [];
        foreach ($entities as $entity) {
            $edges[] = new FeaturedEntityEdge(
                $entity,
                $cursor
            );
        }
        return $edges;
    }

    /**
     * @param int|null $tenantId
     * @param FeaturedEntityTypeEnum $featuredEntityType - type of featured entities.
     * @return FeaturedUser[]
     * @throws NoTenantFoundException
     */
    public function getAllFeaturedEntities(
        ?int $tenantId = null,
        ?FeaturedEntityTypeEnum $featuredEntityType = null
    ): iterable {
        $tenantId ??= $this->config->get('tenant_id');

        if (!$tenantId) {
            throw new NoTenantFoundException();
        }

        return $this->repository->getFeaturedEntities(
            tenantId: $tenantId,
            type: $featuredEntityType,
            withPagination: false
        );
    }
}
