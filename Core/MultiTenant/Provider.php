<?php

namespace Minds\Core\MultiTenant;

use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Di\Di;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(Services\MultiTenantBootService::class, function (Di $di): Services\MultiTenantBootService {
            return new Services\MultiTenantBootService($di->get('Config'));
        });
    }
}
