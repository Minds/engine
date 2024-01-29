<?php
/**
 * Stripe keys module.
 * Repsonsible for securely storing the stripe keys.
 */

namespace Minds\Core\Payments\Stripe\Keys;

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
        (new Provider())->register();
        (new GraphQLMappings())->register();
    }
}
