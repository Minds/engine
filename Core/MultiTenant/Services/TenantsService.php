<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Cache\MultiTenantCacheHandler;
use Minds\Core\MultiTenant\Configs\Repository as TenantConfigRepository;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Repository;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use PDOException;
use Psr\SimpleCache\InvalidArgumentException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class TenantsService
{
    public function __construct(
        private readonly Repository              $repository,
        private readonly TenantConfigRepository  $tenantConfigRepository,
        private readonly MultiTenantCacheHandler $multiTenantCacheHandler,
        private readonly DomainService           $domainService,
        private readonly Config                  $mindsConfig
    ) {
    }

    /**
     * @param int $ownerGuid
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getTenantsByOwnerGuid(
        int $ownerGuid,
        int $limit = 12,
        int $offset = 0,
    ): array {
        return iterator_to_array(
            iterator: $this->repository->getTenants(
                limit: $limit,
                offset: $offset,
                ownerGuid: $ownerGuid,
            )
        );
    }

    /**
     * @param Tenant $tenant
     * @return Tenant
     * @throws GraphQLException
     */
    public function createNetwork(Tenant $tenant): Tenant
    {
        if ($this->mindsConfig->get('tenant_id')) {
            throw new GraphQLException('You are already a tenant and as such cannot create a new tenant.');
        }

        $tenant = $this->repository->createTenant($tenant);

        if ($tenant->config) {
            $this->tenantConfigRepository->upsert(
                tenantId: $tenant->id,
                siteName: $tenant->config->siteName,
                colorScheme: $tenant->config->colorScheme,
                primaryColor: $tenant->config->primaryColor,
            );
        }

        return $tenant;
    }

    /**
     * @param Tenant $tenant
     * @param User $user
     * @return Tenant
     * @throws GraphQLException
     */
    public function createNetworkTrial(
        Tenant $tenant,
        User   $user
    ): Tenant {
        if ($this->mindsConfig->get('tenant_id')) {
            throw new GraphQLException('You are already a tenant and as such cannot create a new tenant.');
        }

        try {
            if (!$this->repository->canHaveTrialTenant($user)) {
                throw new GraphQLException('A network with a trial period has already been claimed for this account');
            }

            $tenant = $this->repository->createTenant(
                tenant: $tenant,
                isTrial: true
            );

            if ($tenant->config) {
                $this->tenantConfigRepository->upsert(
                    tenantId: $tenant->id,
                    siteName: $tenant->config->siteName,
                    colorScheme: $tenant->config->colorScheme,
                    primaryColor: $tenant->config->primaryColor,
                );
            }

            return $tenant;
        } catch (ServerErrorException|PDOException $e) {
            throw new GraphQLException(message: 'Failed to create trial network', code: 500, previous: $e);
        }
    }

    /**
     * @param User $user
     * @return Tenant
     * @throws ServerErrorException
     * @throws NotFoundException
     */
    public function getTrialNetworkByOwner(User $user): Tenant
    {
        return $this->repository->getTrialTenantForOwner($user);
    }

    /**
     * @param Tenant $tenant
     * @param TenantPlanEnum $plan
     * @return Tenant
     * @throws InvalidArgumentException
     */
    public function upgradeNetworkTrial(
        Tenant         $tenant,
        TenantPlanEnum $plan
    ): Tenant {
        $tenant = $this->repository->upgradeTrialTenant($tenant, $plan);

        $this->multiTenantCacheHandler->resetTenantCache(tenant: $tenant, domainService: $this->domainService);

        return $tenant;
    }
}
