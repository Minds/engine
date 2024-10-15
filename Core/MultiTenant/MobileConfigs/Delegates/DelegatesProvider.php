<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Email\V2\Campaigns\Recurring\MobileAppPreviewReady\MobileAppPreviewReadyEmailer;
use Minds\Core\MultiTenant\MobileConfigs\Delegates\MobileAppPreviewReadyEmailDelegate;
use Minds\Core\Security\Rbac\Services\RolesService;

class DelegatesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            MobileAppPreviewReadyEmailDelegate::class,
            fn (Di $di): MobileAppPreviewReadyEmailDelegate => new MobileAppPreviewReadyEmailDelegate(
                $di->get(MobileAppPreviewReadyEmailer::class),
                $di->get(RolesService::class),
                $di->get('Logger')
            )
        );
    }
}
