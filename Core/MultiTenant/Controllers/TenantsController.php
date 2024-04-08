<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\AutoLogin\AutoLoginService;
use Minds\Core\MultiTenant\Enums\TenantUserRoleEnum;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\TenantsService;
use Minds\Core\MultiTenant\Services\TenantUsersService;
use Minds\Core\MultiTenant\Types\TenantLoginRedirectDetails;
use Minds\Core\MultiTenant\Types\TenantUser;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class TenantsController
{
    public function __construct(
        private readonly TenantsService     $networksService,
        private readonly TenantUsersService $usersService,
        private readonly AutoLoginService   $autoLoginService,
        private readonly ExperimentsManager $experimentsManager,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param User $loggedInUser
     * @param int $first
     * @param int $last
     * @return Tenant[]
     */
    #[Query]
    #[Logged]
    public function getTenants(
        #[InjectUser] User $loggedInUser,
        int                $first = 12,
        int                $last = 0,
    ): array {
        return $this->networksService->getTenantsByOwnerGuid(
            ownerGuid: (int)$loggedInUser->getGuid(),
            limit: $first,
            offset: $last,
        );
    }

    #[Mutation]
    #[Logged]
    public function createTenant(
        Tenant             $tenant,
        #[InjectUser] User $loggedInUser
    ): Tenant {
        if (!$this->experimentsManager->setUser($loggedInUser)->isOn('tmp-create-networks')) {
            throw new ForbiddenException();
        }

        return $this->networksService->createNetwork($tenant);
    }

    /**
     * Create a trial tenant network.
     * @param Tenant $tenant - tenant details to create trial with.
     * @return TenantLoginRedirectDetails - redirect details for request.
     * @throws GraphQLException
     */
    #[Mutation]
    #[Logged]
    public function tenantTrial(
        Tenant             $tenant,
        #[InjectUser] User $loggedInUser,
    ): TenantLoginRedirectDetails {
        $createdTenant = $this->networksService->createNetworkTrial($tenant, $loggedInUser);

        try {
            $this->usersService->createNetworkRootUser(
                networkUser: new TenantUser(
                    guid: (int) Guid::build(),
                    username: $loggedInUser->getUsername(),
                    tenantId: $createdTenant->id,
                    role: TenantUserRoleEnum::OWNER,
                    plainPassword: openssl_random_pseudo_bytes(128)
                ),
                sourceUser: $loggedInUser
            );

            return new TenantLoginRedirectDetails(
                tenant: $createdTenant,
                loginUrl: $this->autoLoginService->buildLoginUrl(
                    tenantId: $createdTenant->id
                ),
                jwtToken: $this->autoLoginService->buildJwtToken(
                    tenantId: $createdTenant->id,
                    loggedInUser: $loggedInUser
                ),
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
            return new TenantLoginRedirectDetails(tenant: $createdTenant);
        }
    }
}
