<?php
declare(strict_types=1);

namespace Minds\Core\Analytics\TenantAdminAnalytics;

use Minds\Core\Config\Config;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\MultiTenant\Configs\Manager as MultiTenantConfigManager;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(Controllers\AdminAnalyticsController::class, function ($di) {
            return new Controllers\AdminAnalyticsController(
                // $di->get(Manager::class),
                // $di->get('Logger')
            );
        });
    }
}
