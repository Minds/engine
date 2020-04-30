<?php

namespace Minds\Core\DismissibleWidgets;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('DismissibleWidgets\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('DismissibleWidgets\Controllers', function ($di) {
            return new Controllers();
        });
    }
}
