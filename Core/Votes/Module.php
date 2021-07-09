<?php
/**
 * Votes module.
 */

namespace Minds\Core\Votes;

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
        $events = new Events();
        $events->register();
    }
}
