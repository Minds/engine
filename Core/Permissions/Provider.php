<?php

namespace Minds\Core\Permissions;

use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Permissions\Entities;
use Minds\Core\Permissions\Manager;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Permissions\Entities\Manager', function ($di) {
            return new Entities\Manager();
        });

        $this->di->bind('Permissions\Manager', function ($di) {
            return new Manager();
        });
    }
}
