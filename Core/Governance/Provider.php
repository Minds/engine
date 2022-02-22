<?php

namespace Minds\Core\Governance;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind('Governance\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('Governance\Controller', function ($di) {
            return new Controller();
        });
    }
}
