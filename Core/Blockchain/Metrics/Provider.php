<?php
/**
 * Minds Blockchain Metrics Provider.
 */

namespace Minds\Core\Blockchain\Metrics;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Blockchain\Metrics\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
        $this->di->bind('Blockchain\Metrics\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
    }
}
