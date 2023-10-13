<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs;

use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(Controllers\Controller::class, function ($di) {
            return new Controllers\Controller(
                $di->get(Manager::class),
                $di->get('Logger')
            );
        });
        $this->di->bind(Manager::class, function ($di) {
            return new Manager(
                $di->get(MultiTenantDataService::class),
                $di->get(DomainService::class),
                $di->get(Repository::class),
                $di->get('Logger'),
                $di->get('Config')
            );
        });
        $this->di->bind(Repository::class, function ($di) {
            return new Repository(
                $di->get('Database\MySQL\Client'),
                $di->get('Logger')
            );
        });
        $this->di->bind(Image\Controller::class, function ($di) {
            return new Image\Controller(
                $di->get(Image\Manager::class),
                $di->get('Config')
            );
        });
        $this->di->bind(Image\Manager::class, function ($di) {
            return new Image\Manager(
                $di->get('Media\Imagick\Manager'),
                $di->get('Config')
            );
        });
    }
}
