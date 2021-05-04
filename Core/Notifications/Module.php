<?php
/**
 * Notifications module.
 */

namespace Minds\Core\Notifications;

use Minds\Interfaces\ModuleInterface;

/**
 * Notifications Module
 * @package Minds\Core\Notifications
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
