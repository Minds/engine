<?php

/**
 * Minds Features Provider.
 *
 * @author emi
 */

namespace Minds\Core\Features;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Features provider.
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Features\Canary', function ($di) {
            return new Canary();
        }, ['useFactory' => true]);
    }
}
