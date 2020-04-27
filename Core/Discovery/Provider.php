<?php

namespace Minds\Core\Discovery;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('Discovery\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('Discovery\Controllers', function ($di) {
            return new Controllers();
        });
    }
}
