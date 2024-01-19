<?php

namespace Minds\Core\MultiTenant\CustomPages;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Provider as DiProvider;
use  Minds\Core\MultiTenant\CustomPages\Services\Service;
use Minds\Core\MultiTenant\CustomPages\Controllers\Controller;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(Controller::class, function ($di) {
            return new Controller(
                $di->get(Service::class),
            );
        });

        $this->di->bind(Service::class, function ($di) {
            return new Service(
                $di->get(Repository::class),
            );
        });

        $this->di->bind(Repository::class, function ($di) {
            return new Repository(
                $di->get(Client::class),
                $di->get(Config::class),
                $di->get('Logger'),
            );
        });
    }
}
