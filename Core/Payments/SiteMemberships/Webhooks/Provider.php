<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Webhooks;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipsRenewalsService;
use Minds\Core\Payments\SiteMemberships\Webhooks\Controllers\SiteMembershipWebhooksPsrController;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind(
            SiteMembershipWebhooksPsrController::class,
            fn (Di $di): SiteMembershipWebhooksPsrController => new SiteMembershipWebhooksPsrController(
                siteMembershipsRenewalsService: $di->get(SiteMembershipsRenewalsService::class)
            )
        );
    }
}
