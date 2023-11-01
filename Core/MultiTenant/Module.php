<?php
namespace Minds\Core\MultiTenant;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /** @var array */
    public $submodules = [
        Configs\Module::class,
    ];

    /**
     * OnInit
     */
    public function onInit()
    {
        (new Provider())->register();
    }
}
