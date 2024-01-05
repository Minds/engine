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
        Supermind\Module::class,
        TwitterSync\Module::class,
        Activity\RichEmbed\Module::class,
        HideEntities\Module::class,
        GraphQL\Module::class,
        RSS\Module::class,
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
