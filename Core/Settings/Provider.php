<?php

namespace Minds\Core\Settings;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        Di::_()->bind('Settings\Controller', function ($di): Controller {
            return new Controller();
        });
        Di::_()->bind('Settings\Manager', function ($di): Manager {
            return new Manager();
        });
        Di::_()->bind('Settings\Repository', function ($di): Repository {
            return new Repository();
        });
    }
}
