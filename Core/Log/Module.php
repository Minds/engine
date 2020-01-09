<?php
/**
 * Module
 *
 * @author edgebal
 */

namespace Minds\Core\Log;

use Minds\Interfaces\ModuleInterface;

/**
 * Module class
 *
 * @package Minds\Core\Log
 */
class Module implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    public function onInit(): void
    {
        (new Provider())->register();
    }
}
