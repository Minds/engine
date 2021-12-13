<?php

namespace Minds\Core\Blockchain\SKALE;

use Minds\Interfaces\ModuleInterface;

/**
 * SKALE Module.
 * @package Minds\Core\Blockchain\SKALE
 */
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
