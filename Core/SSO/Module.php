<?php
/**
 * Module
 * @author edgebal
 */

namespace Minds\Core\SSO;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * Executed onInit
     * @return void
     */
    public function onInit()
    {
        (new Provider())->register();
    }
}
