<?php

namespace Minds\Core\Boost;

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
        $this->di->bind('Boost\Repository', function ($di) {
            return new Repository();
        }, ['useFactory' => true]);

        $this->di->bind('Boost\Network', function ($di) {
            return new Network(Client::build('MongoDB'));
        }, ['useFactory' => true]);
        $this->di->bind('Boost\Network\Manager', function ($di) {
            return new Network\Manager;
        }, ['useFactory' => false]);
        $this->di->bind('Boost\Network\Metrics', function ($di) {
            return new Network\Metrics();
        }, ['useFactory' => false]);
        $this->di->bind('Boost\Network\Expire', function ($di) {
            return new Network\Expire();
        }, ['useFactory' => false]);

        //
        $this->di->bind('Boost\LiquiditySpot\Manager', function ($di) {
            return new LiquiditySpot\Manager();
        });

        $this->di->bind('Boost\LiquiditySpot\Controller', function ($di) {
            return new LiquiditySpot\Controller();
        });
    }
}
