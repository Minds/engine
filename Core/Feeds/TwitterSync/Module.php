<?php
/**
 * Module
 * @author mark
 */

namespace Minds\Core\Feeds\TwitterSync;

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
