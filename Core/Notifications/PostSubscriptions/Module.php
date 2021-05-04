<?php
/**
 * Post subscriptions module.
 */

namespace Minds\Core\Notifications\PostSubscriptions;

use Minds\Interfaces\ModuleInterface;

/**
 * Post subscriptions module
 * @package Minds\Core\Notifications\PostSubscriptions
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
