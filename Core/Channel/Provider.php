<?php

namespace Minds\Core\Channel;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('Channel\Manager', function ($di) {
            return new Manager();
        });

        $this->di->bind('Channel\Controller', function ($di) {
            return new Controller();
        });
    }
}
