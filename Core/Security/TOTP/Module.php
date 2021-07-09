<?php
/**
 * TOTP module.
 */

namespace Minds\Core\Security\TOTP;

use Minds\Interfaces\ModuleInterface;

/**
 * TOTP Module
 * @package Minds\Core\Security\TOTP
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
