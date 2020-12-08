<?php
/**
 * UniqueOnChain module.
 */

namespace Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * OnInit.
     */
    public function onInit()
    {
        $provider = new Provider();
        $provider->register();
        $routes = new Routes();
        $routes->register();
    }
}
