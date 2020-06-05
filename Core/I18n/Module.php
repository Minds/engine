<?php
namespace Minds\Core\I18n;

use Minds\Interfaces\ModuleInterface;

/**
 * I18n Module
 * @package Minds\Core\I18n
 */
class Module implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    public function onInit()
    {
        // DI
        (new Provider())->register();
    }
}
