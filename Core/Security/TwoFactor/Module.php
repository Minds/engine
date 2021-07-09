<?php
/**
 * TwoFactor module.
 */

namespace Minds\Core\Security\TwoFactor;

use Minds\Interfaces\ModuleInterface;

/**
 * TwoFactorModule
 * @package Minds\Core\Security\TwoFactor
 */
class Module implements ModuleInterface
{
    /**
     * OnInit.
     */
    public function onInit()
    {
        $provider = new Provider();
        $provider->register();
    }
}
