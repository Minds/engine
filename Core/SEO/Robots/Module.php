<?php
declare(strict_types=1);

namespace Minds\Core\SEO\Robots;

use Minds\Interfaces\ModuleInterface;

/**
 * SEO Module.
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
