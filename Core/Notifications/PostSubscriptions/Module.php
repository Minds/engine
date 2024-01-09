<?php
/**
 * Post Subscriptions (Notifications)module.
 */

namespace Minds\Core\Notifications\PostSubscriptions;

use Minds\Interfaces\ModuleInterface;

/**
 * Notifications Module (v3)
 * @package Minds\Core\Notifications
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
        (new Provider())->register();
        (new GraphQLMappings)->register();
    }
}
