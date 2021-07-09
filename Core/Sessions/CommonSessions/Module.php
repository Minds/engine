<?php
/**
 * Common Session Module
 */

namespace Minds\Core\Sessions\CommonSessions;

use Minds\Interfaces\ModuleInterface;

/**
 * Common Sessions Module
 * @package Minds\Core\Sessions\CommonSessions
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
