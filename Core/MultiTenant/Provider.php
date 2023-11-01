<?php

namespace Minds\Core\MultiTenant;

use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(Services\DomainService::class, function (Di $di): Services\DomainService {
            return new Services\DomainService(
                $di->get('Config'),
                $di->get(MultiTenantDataService::class),
                $di->get('Cache\PsrWrapper')
            );
        });

        $this->di->bind(Services\MultiTenantDataService::class, function (Di $di): Services\MultiTenantDataService {
            return new Services\MultiTenantDataService($di->get(Repository::class));
        });

        $this->di->bind(Services\MultiTenantBootService::class, function (Di $di): Services\MultiTenantBootService {
            return new Services\MultiTenantBootService(
                $di->get('Config'),
                $di->get(Services\DomainService::class),
                $di->get(MultiTenantDataService::class),
            );
        }, ['useFactory' => true]);

        $this->di->bind(Repository::class, function (Di $di): Repository {
            return new Repository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                logger: $di->get('Logger')
            );
        });
    }
}
