<?php
/**
 * Sessions module.
 */

namespace Minds\Core\Sessions;

use Minds\Interfaces\ModuleInterface;

/**
 * Sessions Module
 * @package Minds\Core\Sessions
 */
class Module implements ModuleInterface
{
    /** @var array $submodules */
    public $submodules = [
        CommonSessions\Module::class,
    ];


    /**
     * OnInit.
     */
    public function onInit()
    {
        $provider = new SessionsProvider();
        $provider->register();
    }
}
