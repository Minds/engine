<?php
/**
 * Minds Experiments Provider
 */

namespace Minds\Core\Experiments;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Experiments\Manager', function ($di) {
            return new Manager;
        }, ['useFactory' => false]);

        $this->di->bind('Experiments\Controller', function ($di) {
            return new Controller;
        }, ['useFactory' => true]);

        $this->di->bind('Experiments\Cookie\Manager', function ($di) {
            return new Cookie\Manager;
        }, ['useFactory' => true]);
    }
}
