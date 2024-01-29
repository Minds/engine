<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipManagementService;

class SiteMembershipManagementController
{
    public function __construct(
        private readonly SiteMembershipManagementService $siteMembershipManagementService
    )
    {
    }
}
