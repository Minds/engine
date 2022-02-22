<?php
/**
 * Social Compass module
 */

namespace Minds\Core\Governance;

use Minds\Interfaces\ModuleInterface;

/**
 * Social Compass Module (v3)
 * @package Minds\Core\Governance
 */
class Module implements ModuleInterface
{
    public function onInit()
    {
        $provider = new Provider();
        $provider->register();

        $routes = new Routes();
        $routes->register();
    }
}
