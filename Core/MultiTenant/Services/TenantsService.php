<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

use Minds\Core\Analytics\PostHog\PostHogService;
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
        private readonly Config                  $mindsConfig,
        private readonly PostHogService          $postHogService,
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

        $this->tenantConfigRepository->upsert(
            tenantId: $tenant->id,
            siteName: $tenant->config?->siteName ?? null,
            colorScheme: $tenant->config?->colorScheme ?? null,
            primaryColor: $tenant->config?->primaryColor ?? null,
        );

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

            $this->tenantConfigRepository->upsert(
                tenantId: $tenant->id,
                siteName: $tenant->config?->siteName ?? null,
                colorScheme: $tenant->config?->colorScheme ?? null,
                primaryColor: $tenant->config?->primaryColor ?? null,
            );

            $this->postHogService->capture(
                event: 'tenant_trial_start',
                user: $user,
                properties: [
                    'tenant_id' => $tenant->id,
                ],
                setOnce: [
                    'tenant_trial_started' => date('c', $tenant->trialStartTimestamp),
                ]
            );

            return $tenant;
        } catch (ServerErrorException|PDOException $e) {
            $this->postHogService->capture(
                event: 'tenant_trial_start_failed',
                user: $user,
            );

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
     * When a tenant subscription changes, call this function to update the subscription
     * and plan info
     */
    public function upgradeTenant(
        Tenant         $tenant,
        TenantPlanEnum $plan,
        string         $stripeSubscription,
        User           $user,
    ): Tenant {
        $tenant = $this->repository->upgradeTenant($tenant, $plan, $stripeSubscription);
        $tenant->plan = $plan;
        $tenant->stripeSubscription = $stripeSubscription;

        $this->multiTenantCacheHandler->resetTenantCache(tenant: $tenant, domainService: $this->domainService);

        $this->postHogService->capture(
            event: 'tenant_upgrade',
            user: $user,
            properties: [
                'tenant_id' => $tenant->id,
                'tenant_plan' => $plan->name,
            ],
            setOnce: [
                'tenant_upgrade_converted' => date('c', time()),
            ]
        );

        return $tenant;
    }

}
