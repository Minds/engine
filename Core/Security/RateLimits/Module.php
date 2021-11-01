<?php
/**
 * RateLimits module.
 */

namespace Minds\Core\Security\RateLimits;

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
        $events = new Events();
        $events->register();
    }
}
