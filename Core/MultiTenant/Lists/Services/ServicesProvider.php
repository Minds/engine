<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Lists\Services;

use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\MultiTenant\Lists\Enums\TenantListRepositoryTypeEnum;
use Minds\Core\MultiTenant\Lists\Repositories\TenantListRepositoryInterface;

class ServicesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            TenantChannelsListService::class,
            fn (): TenantChannelsListService => new TenantChannelsListService(
                tenantListRepository: $this->di->get(TenantListRepositoryInterface::class, ['repositoryType'=>TenantListRepositoryTypeEnum::CHANNELS]),
                entitiesBuilder: $this->di->get('EntitiesBuilder')
            )
        );
        $this->di->bind(
            TenantGroupsListService::class,
            fn (): TenantGroupsListService => new TenantGroupsListService(
                tenantListRepository: $this->di->get(TenantListRepositoryInterface::class, ['repositoryType'=>TenantListRepositoryTypeEnum::GROUPS]),
                entitiesBuilder: $this->di->get('EntitiesBuilder')
            )
        );
    }
}
