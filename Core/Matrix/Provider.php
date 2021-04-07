<?php
/**
 * Minds Matrix Provider.
 */

namespace Minds\Core\Matrix;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Matrix\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Matrix\WellKnownController', function ($di) {
            return new WellKnownController();
        }, ['useFactory' => false]);
        $this->di->bind('Matrix\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
    }
}
