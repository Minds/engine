<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Lists\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\MultiTenant\Lists\Services\TenantChannelsListService;
use Minds\Core\MultiTenant\Lists\Services\TenantGroupsListService;

class ControllersProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            ChannelsListPsrController::class,
            fn (Di $di): ChannelsListPsrController => new ChannelsListPsrController(
                tenantChannelsListService: $di->get(TenantChannelsListService::class)
            )
        );
        $this->di->bind(
            GroupsListPsrController::class,
            fn (Di $di): GroupsListPsrController => new GroupsListPsrController(
                tenantGroupsListService: $di->get(TenantGroupsListService::class)
            )
        );
    }
}
