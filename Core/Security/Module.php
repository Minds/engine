<?php
/**
 * Security module.
 */

namespace Minds\Core\Security;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /** @var array $submodules */
    public $submodules = [
        Block\Module::class,
        RateLimits\Module::class,
        TOTP\Module::class,
        TwoFactor\Module::class,
        Password\Module::class,
    ];

    /**
     * OnInit.
     */
    public function onInit()
    {
        $provider = new SecurityProvider();
        $provider->register();
        $events = new Events();
        $events->register();
    }
}
