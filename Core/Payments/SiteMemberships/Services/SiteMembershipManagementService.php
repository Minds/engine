<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;

class SiteMembershipManagementService
{
    public function __construct(
        private readonly SiteMembershipRepository $siteMembershipRepository
    ) {
    }
}
