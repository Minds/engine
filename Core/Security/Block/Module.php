<?php
/**
 * Block module.
 */

namespace Minds\Core\Security\Block;

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
