<?php
/**
 * Minds UnstoppableDomains Provider.
 */

namespace Minds\Core\Blockchain\UnstoppableDomains;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Blockchain\UnstoppableDomains\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
    }
}
