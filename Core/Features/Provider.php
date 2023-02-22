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
        // ojm commented this
        // $this->di->bind('Features\Keys', function () {
        //     return [];
        // });

        // ojm commented this
        // $this->di->bind('Features\Manager', function ($di) {
        //     return new Manager();
        // }, ['useFactory' => true]);

        $this->di->bind('Features\Canary', function ($di) {
            return new Canary();
        }, ['useFactory' => true]);
    }
}
