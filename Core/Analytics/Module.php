<?php
declare(strict_types=1);

namespace Minds\Core\Analytics;

use Minds\Core\Analytics\AnalyticsProvider;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public array $submodules = [
        TenantAdminAnalytics\Module::class,
        PostHog\Module::class,
    ];

    public function onInit()
    {
        (new AnalyticsProvider())->register();
        (new Routes())->register();
    }
}
