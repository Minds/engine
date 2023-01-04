<?php

namespace Minds\Core\Feeds\HideEntities;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind(Manager::class, function ($di) {
            return new Manager();
        });
        $this->di->bind(Repository::class, function ($di) {
            return new Repository();
        });
        $this->di->bind(Controller::class, function ($di) {
            return new Controller();
        });
    }
}
