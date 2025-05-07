<?php
/**
 * Audit module.
 */

namespace Minds\Core\Security\Audit;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public function onInit()
    {
        $provider = new Provider();
        $provider->register();
        (new Routes)->register();
    }
}
