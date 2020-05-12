<?php
namespace Minds\Core\Channels\Groups;

use Minds\Interfaces\ModuleInterface;

/**
 * Groups Module
 * @package Minds\Core\Channels\Groups
 */
class Module implements ModuleInterface
{
    /**
     * Module initialization hook
     */
    public function onInit(): void
    {
        // DI Provider
        (new Provider())->register();

        // Routes
        (new Routes())->register();
    }
}
