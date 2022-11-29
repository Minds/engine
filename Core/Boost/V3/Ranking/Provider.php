<?php

namespace Minds\Core\Boost\V3\Ranking;

use Minds\Core\Boost\Network;
use Minds\Core\Data;
use Minds\Core\Data\Client;
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
        $this->di->bind('Boost\V3\Ranking\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => true]);
        $this->di->bind('Boost\V3\Ranking\Repository', function ($di) {
            return new Repository();
        }, ['useFactory' => true]);
    }
}
