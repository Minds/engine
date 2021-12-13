<?php

namespace Minds\Core\Blockchain\SKALE;

use Minds\Core\Blockchain\SKALE\Faucet\FaucetLimiter;
use Minds\Core\Di\Provider as DiProvider;

/**
 * SKALE Provider.
 * @package Minds\Core\Blockchain\SKALE
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Blockchain\SKALE\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Blockchain\SKALE\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
        $this->di->bind('Blockchain\SKALE\FaucetLimiter', function ($di) {
            return new FaucetLimiter();
        }, ['useFactory' => false]);
    }
}
