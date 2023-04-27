<?php

namespace Minds\Core\Boost;

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

        //
        $this->di->bind('Boost\LiquiditySpot\Manager', function ($di) {
            return new LiquiditySpot\Manager();
        });

        $this->di->bind('Boost\LiquiditySpot\Controller', function ($di) {
            return new LiquiditySpot\Controller();
        });
    }
}
