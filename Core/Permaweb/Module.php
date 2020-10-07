<?php
/**
 * Permaweb module.
 */

namespace Minds\Core\Permaweb;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * OnInit.
     */
    public function onInit()
    {
        $provider = new PermawebProvider();
        $provider->register();
    }
}
