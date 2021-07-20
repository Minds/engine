<?php

namespace Minds\Core\Helpdesk\Zendesk;

use Minds\Interfaces\ModuleInterface;

/**
 * Zendesk Module
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
