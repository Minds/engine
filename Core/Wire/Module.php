<?php
namespace Minds\Core\Wire;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /** @var array $submodules */
    public $submodules = [
        Paywall\Module::class,
    ];

    /**
     * Executed onInit
     */
    public function onInit()
    {
        $events = new Events();
        $events->register();
    }
}
