<?php
/**
 * Register module.
 */

namespace Minds\Core\Register;

use Minds\Interfaces\ModuleInterface;

/**
 * Register Module
 * @package Minds\Core\Register
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
