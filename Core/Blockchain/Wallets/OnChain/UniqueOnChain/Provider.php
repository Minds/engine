<?php
/**
 * Minds UniqueOnChain Provider.
 */

namespace Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Blockchain\UniqueOnChain\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Blockchain\UniqueOnChain\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
    }
}
