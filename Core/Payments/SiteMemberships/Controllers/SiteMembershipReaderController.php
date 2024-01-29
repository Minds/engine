<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;

class SiteMembershipReaderController
{
    public function __construct(
        private readonly SiteMembershipReaderService $siteMembershipReaderService
    )
    {
    }
}
