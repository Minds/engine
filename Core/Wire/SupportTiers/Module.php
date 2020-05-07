<?php
namespace Minds\Core\Wire\SupportTiers;

use Minds\Interfaces\ModuleInterface;

/**
 * Wire Support Tiers Module
 * @package Minds\Core\Wire\SupportTiers
 */
class Module implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    public function onInit()
    {
        // DI Provider
        (new Provider())->register();

        // Routes
        (new Routes())->register();
    }
}
