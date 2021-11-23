<?php

namespace Minds\Core\Blockchain\SKALE;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Blockchain\Skale\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Blockchain\Skale\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
    }
}
