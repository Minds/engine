<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;

class ServicesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            SiteMembershipManagementService::class,
            fn (Di $di): SiteMembershipManagementService => new SiteMembershipManagementService(
                siteMembershipRepository: $di->get(SiteMembershipRepository::class)
            )
        );
        $this->di->bind(
            SiteMembershipReaderService::class,
            fn (Di $di): SiteMembershipReaderService => new SiteMembershipReaderService(
                siteMembershipRepository: $di->get(SiteMembershipRepository::class)
            )
        );
    }
}
