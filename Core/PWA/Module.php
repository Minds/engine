<?php
declare(strict_types=1);

namespace Minds\Core\PWA;

use Minds\Interfaces\ModuleInterface;

/**
 * PWA Module.
 */
class Module implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    public function onInit()
    {
        $provider = new Provider();
        $provider->register();
        $routes = new Routes();
        $routes->register();
    }
}
