<?php
/**
 * Stripe module.
 */

namespace Minds\Core\Payments\Stripe;

use Minds\Interfaces\ModuleInterface;

/**
 * Stripe module
 * @package Minds\Core\Payments\Stripe
 */
class Module implements ModuleInterface
{
    /** @var array $submodules */
    public $submodules = [];

    /**
     * OnInit.
     */
    public function onInit()
    {
        $routes = new Routes();
        $routes->register();
    }
}
