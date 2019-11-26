<?php

namespace Minds\Core\Boost;

use Minds\Core\Boost\Network;
use Minds\Core\Data;
use Minds\Core\Data\Client;
use Minds\Core\Di\Provider;

/**
 * Boost Providers
 */
class BoostProvider extends Di\Provider
{
    /**
     * Registers providers onto DI
     * @return void
     * @throws Di\ImmutableException
     */
    public function register()
    {
        $this->di->bind('Boost\Repository', function ($di) {
            return new Repository();
        }, ['useFactory' => true]);

        $this->di->bind('Boost\Network\Manager', function ($di) {
            return new Network\Manager();
        }, ['useFactory' => false]);

        $this->di->bind('Boost\Network\Iterator', function ($di) {
            return new Network\Iterator();
        }, ['useFactory' => false]);

        $this->di->bind('Boost\Network\Review', function ($di) {
            return new Network\Review();
        }, ['useFactory' => false]);

        $this->di->bind('Boost\Peer\Review', function ($di) {
            return new Peer\Review();
        }, ['useFactory' => false]);

        $this->di->bind('Boost\Payment', function ($di) {
            return new Payment();
        }, ['useFactory' => true]);
    }

}
