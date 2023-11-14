<?php

namespace Minds\Core\Security\Rbac;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Provider as DiProvider;
use  Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\Security\Rbac\Controllers\PermissionsController;
use Minds\Core\Security\Rbac\Entities;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Permissions\Entities\Manager', function ($di) {
            return new Entities\Manager();
        });

        $this->di->bind(PermissionsController::class, function ($di) {
            return new PermissionsController($di->get(RolesService::class));
        });

        $this->di->bind(RolesService::class, function ($di) {
            return new RolesService(
                $di->get(Config::class),
                $di->get(Repository::class),
            );
        });

        $this->di->bind(Repository::class, function ($di) {
            return new Repository($di->get(Config::class), $di->get(Client::class), $di->get('Logger'));
        });
    }
}
