<?php
/**
 * Minds TokenPrices Provider.
 */

namespace Minds\Core\Blockchain\TokenPrices;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Blockchain\TokenPrices\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Blockchain\TokenPrices\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
    }
}
