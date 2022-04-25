<?php

namespace Minds\Core\DID;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('DID\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('DID\Controller', function ($di) {
            return new Controller();
        });
    }
}
