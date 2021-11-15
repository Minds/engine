<?php
/**
 * Social Compass module
 */

namespace Minds\Core\SocialCompass;

use Minds\Interfaces\ModuleInterface;

/**
 * Social Compass Module (v3)
 * @package Minds\Core\SocialCompass
 */
class Module implements ModuleInterface
{
    public array $submodules = [];

    public function onInit()
    {
        $provider = new Provider();
        $provider->register();

        $routes = new Routes();
        $routes->register();
    }
}
