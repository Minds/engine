<?php

namespace Minds\Core\Monetization\Partners;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('Monetization\Partners\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('Monetization\Partners\Controllers', function ($di) {
            return new Controllers();
        });
    }
}
