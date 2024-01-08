<?php
/**
 * Email module.
 */

namespace Minds\Core\Email;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public array $submodules = [
        Mautic\Module::class,
        V2\Module::class,
        Invites\Module::class
    ];

    /**
     * OnInit.
     */
    public function onInit()
    {
        $provider = new Provider();
        $provider->register();

        $events = new Events();
        $events->register();

        $routes = new Routes();
        $routes->register();
    }
}
