<?php
/**
 * Module
 *
 * @author edgebal
 */

namespace Minds\Core\Features;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    public function onInit()
    {
        (new Provider())->register();
    }
}
