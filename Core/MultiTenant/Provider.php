<?php

namespace Minds\Core\MultiTenant;

use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Di\Di;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(Services\DomainService::class, function (Di $di): Services\DomainService {
            return new Services\DomainService($di->get('Config'), $di->get(Repository::class), $di->get('Cache\PsrWrapper'));
        });

        $this->di->bind(Services\MultiTenantBootService::class, function (Di $di): Services\MultiTenantBootService {
            return new Services\MultiTenantBootService($di->get('Config'), $di->get(Services\DomainService::class));
        });

        $this->di->bind(Repository::class, function (Di $di): Repository {
            return new Repository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                logger: $di->get('Logger')
            );
        });
    }
}
