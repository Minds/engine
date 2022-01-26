<?php

namespace Minds\Core\AccountQuality;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind('AccountQuality\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('AccountQuality\Controller', function ($di) {
            return new Controller();
        });
    }
}
