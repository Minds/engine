<?php

namespace Minds\Core\Monetization\EarningsOverview;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('Monetization\EarningsOverview\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('Monetization\EarningsOverview\Controllers', function ($di) {
            return new Controllers();
        });
    }
}
