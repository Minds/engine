<?php
/**
 * Module
 * @author edgebal
 */

namespace Minds\Core\Feeds;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * Executed onInit
     * @return void
     */
    public function onInit(): void
    {
        (new FeedsProvider())->register();
        (new Routes())->register();
    }
}
