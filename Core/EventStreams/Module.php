<?php
/**
 * EventStreams module.
 */

namespace Minds\Core\EventStreams;

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
    }
}
