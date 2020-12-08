<?php
/**
 * LiquidityPositions module.
 */

namespace Minds\Core\Blockchain\LiquidityPositions;

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
