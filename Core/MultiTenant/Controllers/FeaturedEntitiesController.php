<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Minds\Core\MultiTenant\Enums\FeaturedEntityTypeEnum;
use Minds\Core\MultiTenant\Services\FeaturedEntityService;
use Minds\Core\MultiTenant\Types\FeaturedEntity;
use Minds\Core\MultiTenant\Types\FeaturedEntityConnection;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;

class FeaturedEntitiesController
{
    public function __construct(
        private readonly FeaturedEntityService $service
    ) {
    }

    /**
     * Gets featured entities.
     * @param FeaturedEntityTypeEnum $type - type of featured entities.
     * @param int $after - load after cursor.
     * @param int $first - limit of entities to load.
     * @return FeaturedEntityConnection - featured entities connection.
     */
    #[Query]
    public function getFeaturedEntities(
        FeaturedEntityTypeEnum $type,
        int $after = 0,
        int $first = 12
    ): FeaturedEntityConnection {
        return $this->service->getFeaturedEntities(
            type: $type,
            loadAfter: $after,
            limit: $first
        );
    }

    /**
     * Stores featured entity.
     * @param FeaturedEntity $featuredEntity - featured entity to store.
     * @return FeaturedEntity - stored featured entity.
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function storeFeaturedEntity(
        FeaturedEntity $featuredEntity,
        #[InjectUser] ?User $loggedInUser = null,
    ): FeaturedEntity {
        return $this->service->storeFeaturedEntity($featuredEntity, $loggedInUser);
    }

    /**
     * Deletes featured entity.
     * @param string $entityGuid - guid of featured entity to delete.
     * @return bool - true if featured entity was deleted, false otherwise.
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function deleteFeaturedEntity(
        string $entityGuid,
        #[InjectUser] ?User $loggedInUser = null
    ): bool {
        return $this->service->deleteFeaturedEntity((int) $entityGuid);
    }
}
