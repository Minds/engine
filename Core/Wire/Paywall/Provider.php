<?php

namespace Minds\Core\Wire\Paywall;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

/**
 * Wire Paywall Providers
 */
class Provider extends DiProvider
{
    /**
     * Registers providers onto DI
     * @return null
     */
    public function register()
    {
        $this->di->bind('Wire\Paywall\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => true]);
    }
}
