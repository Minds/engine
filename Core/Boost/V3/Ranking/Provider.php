<?php

namespace Minds\Core\Boost\V3\Ranking;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di;

/**
 * Boost Providers
 */
class Provider extends Di\Provider
{
    /**
     * Registers providers onto DI
     * @return void
     */
    public function register()
    {
        $this->di->bind(Manager::class, function ($di) {
            return new Manager();
        }, ['useFactory' => true]);
        $this->di->bind(Repository::class, function ($di) {
            return new Repository(
                mysqlHandler: $di->get(Client::class),
                config: $di->get(Config::class),
                logger: $di->get('Logger'),
            );
        }, ['useFactory' => true]);
    }
}
