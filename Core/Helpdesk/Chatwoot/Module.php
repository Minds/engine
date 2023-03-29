<?php
declare(strict_types=1);

namespace Minds\Core\Helpdesk\Chatwoot;

use Minds\Interfaces\ModuleInterface;

/**
 * Chatwoot Module
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
