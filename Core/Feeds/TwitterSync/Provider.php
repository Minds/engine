<?php

namespace Minds\Core\Feeds\TwitterSync;

use Minds\Core\Di;
use Minds\Core\Entities\Actions\Save;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('Feeds\TwitterSync\Manager', function ($di) {
            return new Manager(new Client(), $di->get('Feeds\TwitterSync\Repository'), $di->get('Config'), $di->get('EntitiesBuilder'), new Save(), new Delegates\ChannelLinksDelegate($di->get('EntitiesBuilder')));
        });
        $this->di->bind('Feeds\TwitterSync\Repository', function ($di) {
            return new Repository($di->get('Database\Cassandra\Cql'));
        });
        $this->di->bind('Feeds\TwitterSync\Controller', function ($di) {
            return new Controller($di->get('Feeds\TwitterSync\Manager'));
        });
    }
}
