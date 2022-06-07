<?php
/**
 * Entities Ops module.
 * Soon to be a submodule
 */

namespace Minds\Core\Entities\Ops;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * OnInit.
     */
    public function onInit()
    {
        $events = new Events();
        $events->register();
    }
}
