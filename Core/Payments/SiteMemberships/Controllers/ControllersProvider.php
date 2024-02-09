<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipManagementService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;

class ControllersProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            SiteMembershipManagementController::class,
            fn (Di $di): SiteMembershipManagementController => new SiteMembershipManagementController(
                siteMembershipManagementService: $di->get(SiteMembershipManagementService::class)
            )
        );

        $this->di->bind(
            SiteMembershipReaderController::class,
            fn (Di $di): SiteMembershipReaderController => new SiteMembershipReaderController(
                siteMembershipReaderService: $di->get(SiteMembershipReaderService::class)
            )
        );
    }
}
