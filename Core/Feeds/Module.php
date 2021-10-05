<?php
/**
 * Module
 * @author edgebal
 */

namespace Minds\Core\Feeds;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public $submodules = [
        TwitterSync\Module::class,
        Activity\RichEmbed\Module::class,
    ];

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
