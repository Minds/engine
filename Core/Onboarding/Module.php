<?php
/**
 * Onboarding module.
 */

namespace Minds\Core\Onboarding;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /** @var array */
    public $submodules = [
        V5\Module::class
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
