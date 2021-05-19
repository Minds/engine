<?php
/**
 * Notifications module.
 */

namespace Minds\Core\Notifications;

use Minds\Interfaces\ModuleInterface;

/**
 * Notifications Module (v3)
 * @package Minds\Core\Notifications
 */
class Module implements ModuleInterface
{
    /** @var array $submodules */
    public $submodules = [
        // PostSubscriptions\Module::class,
        // UpdateMarkers\Module::class,
    ];

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
