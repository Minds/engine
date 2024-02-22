<?php
declare(strict_types=1);

namespace Minds\Core\Notifications\Push\ManualSend;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /** @var array $submodules */
    public $submodules = [
    ];

    /**
     * OnInit.
     */
    public function onInit()
    {
        $provider = new Provider();
        $provider->register();
        $routes = new Routes();
        $routes->register();
    }
}
