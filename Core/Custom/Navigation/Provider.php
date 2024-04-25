<?php

namespace Minds\Core\Custom\Navigation;

use Minds\Core\Config\Config;
use Minds\Core\Custom\Navigation\Controllers\NavigationController;
use Minds\Core\Data\MySQL;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->di->bind(NavigationController::class, function (Di $di): NavigationController {
            return new NavigationController(
                service: $di->get(CustomNavigationService::class),
            );
        });

        $this->di->bind(CustomNavigationService::class, function (Di $di): CustomNavigationService {
            return new CustomNavigationService(
                repository: $di->get(Repository::class),
                cache: $di->get('Cache\PsrWrapper'),
                config: $di->get(Config::class),
            );
        });

        $this->di->bind(Repository::class, function (Di $di): Repository {
            return new Repository(
                mysqlHandler: $di->get(MySQL\Client::class),
                config: $di->get(Config::class),
                logger: $di->get('Logger'),
            );
        });
    }
}
