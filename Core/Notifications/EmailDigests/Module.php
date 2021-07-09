<?php
/**
 * EmailDigests Notifications module.
 */

namespace Minds\Core\Notifications\EmailDigests;

use Minds\Interfaces\ModuleInterface;

/**
 * Notifications Module (v3)
 * @package Minds\Core\Notifications\EmailDigests
 */
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
    }
}
