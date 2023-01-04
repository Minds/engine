<?php
/**
 * Module
 * @author Mark
 */

namespace Minds\Core\Feeds\HideEntities;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * Executed onInit
     * @return void
     */
    public function onInit(): void
    {
        (new Provider())->register();
        (new Routes())->register();
    }
}
