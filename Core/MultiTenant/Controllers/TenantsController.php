<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\TenantsService;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;

class TenantsController
{
    public function __construct(
        private readonly TenantsService $networksService
    ) {
    }

    /**
     * @param User $loggedInUser
     * @param int $limit
     * @param int $offset
     * @return Tenant[]
     */
    #[Query]
    #[Logged]
    public function getTenants(
        #[InjectUser] User $loggedInUser,
        int $first = 12,
        int $last = 0,
    ): array {
        return $this->networksService->getTenantsByOwnerGuid(
            ownerGuid: (int) $loggedInUser->getGuid(),
            limit: $first,
            offset: $last,
        );
    }

    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")] // TODO - this security check will have to be removed once the purchase flow is implemented
    public function createTenant(
        Tenant $tenant,
        #[InjectUser] User $loggedInUser
    ): Tenant {
        return $this->networksService->createNetwork($tenant);
    }
}
