<?php
/**
 * Push Notifications module.
 */

namespace Minds\Core\Notifications\Push;

use Minds\Interfaces\ModuleInterface;

/**
 * Notifications Module (v3)
 * @package Minds\Core\Notifications
 */
class Module implements ModuleInterface
{
    /** @var array $submodules */
    public $submodules = [
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
