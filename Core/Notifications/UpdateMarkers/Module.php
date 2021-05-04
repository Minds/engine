<?php
/**
 * Update markers module.
 */

namespace Minds\Core\Notifications\UpdateMarkers;

use Minds\Interfaces\ModuleInterface;

/**
 * Update markers module
 * @package Minds\Core\Notifications\UpdateMarkers
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
