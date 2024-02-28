<?php
declare(strict_types=1);

namespace Minds\Core\Analytics\TenantAdminAnalytics;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Configs\Manager as MultiTenantConfigManager;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(Controllers\AdminAnalyticsController::class, function (Di $di): Controllers\AdminAnalyticsController {
            return new Controllers\AdminAnalyticsController(
                $di->get(Services\AdminAnalyticsFetchService::class),
            );
        });

        $this->di->bind(Services\AdminAnalyticsFetchService::class, function (Di $di): Services\AdminAnalyticsFetchService {
            return new Services\AdminAnalyticsFetchService(
                repository: $di->get(Repository::class),
                entitiesBuilder: $di->get(EntitiesBuilder::class),
                votesManager: $di->get('Votes\Manager'),
            );
        });

        $this->di->bind(Repository::class, function (Di $di): Repository {
            return new Repository(
                mysqlHandler: $di->get(Client::class),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            );
        });
    }
}
