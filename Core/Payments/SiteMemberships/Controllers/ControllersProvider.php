<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipBatchService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipManagementService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsManagementService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;

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

        $this->di->bind(
            SiteMembershipSubscriptionsPsrController::class,
            fn (Di $di): SiteMembershipSubscriptionsPsrController => new SiteMembershipSubscriptionsPsrController(
                siteMembershipSubscriptionsService: $di->get(SiteMembershipSubscriptionsService::class)
            )
        );

        $this->di->bind(
            SiteMembershipSubscriptionsManagementPsrController::class,
            fn (Di $di): SiteMembershipSubscriptionsManagementPsrController => new SiteMembershipSubscriptionsManagementPsrController(
                siteMembershipSubscriptionsManagementService: $di->get(SiteMembershipSubscriptionsManagementService::class)
            )
        );

        $this->di->bind(
            SiteMembershipSubscriptionsController::class,
            fn (Di $di): SiteMembershipSubscriptionsController => new SiteMembershipSubscriptionsController(
                siteMembershipSubscriptionsService: $di->get(SiteMembershipSubscriptionsService::class),
                eventsDispatcher: $di->get('EventsDispatcher'),
            )
        );

        $this->di->bind(
            SiteMembershipBatchPsrController::class,
            fn (Di $di) => new SiteMembershipBatchPsrController(
                batchService: $di->get(SiteMembershipBatchService::class)
            ),
        );
    }
}
