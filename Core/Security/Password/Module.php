<?php
/**
 * Password module.
 */

namespace Minds\Core\Security\Password;

use Minds\Interfaces\ModuleInterface;

/**
 * Password Module
 * @package Minds\Core\Security\Password
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
