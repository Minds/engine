<?php
namespace Minds\Core\Wire\Paywall;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * Executed onInit
     */
    public function onInit()
    {
        $provider = new Provider();
        $provider->register();
        $events = new Events();
        $events->register();
    }
}
