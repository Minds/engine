<?php
/**
 * Minds Permaweb Provider
 */

namespace Minds\Core\Permaweb;

use Minds\Core\Di\Provider;
use Minds\Core\Permaweb\Manager;
use Minds\Core\Permaweb\Delegates\DispatchDelegate;
use Minds\Core\Permaweb\Delegates\GenerateIdDelegate;

class PermawebProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Permaweb\Manager', function ($di) {
            return new Manager();
        }, ['useFactory'=>true]);

        $this->di->bind('Permaweb\Delegates\DispatchDelegate', function ($di) {
            return new DispatchDelegate();
        }, ['useFactory'=>true]);

        $this->di->bind('Permaweb\Delegates\GenerateIdDelegate', function ($di) {
            return new GenerateIdDelegate();
        }, ['useFactory'=>true]);
    }
}
