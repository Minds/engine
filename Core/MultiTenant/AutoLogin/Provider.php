<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\AutoLogin;

use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(Controller::class, function (Di $di): Controller {
            return new Controller(
                $di->get(AutoLoginService::class),
                $di->get('Logger')
            );
        });
        $this->di->bind(AutoLoginService::class, function ($di) {
            return new AutoLoginService(
                entitiesBuilder: $di->get(EntitiesBuilder::class),
                sessionsManager: $di->get('Sessions\Manager'),
                tenantDataService: $di->get(MultiTenantDataService::class),
                tenantDomainService: $di->get(DomainService::class),
                tmpStore: $di->get('Cache\Cassandra'),
                jwt: new Jwt(),
                config: $di->get(Config::class),
            );
        });
    }
}
