<?php

namespace Minds\Core\FeedNotices;

use Minds\Interfaces\ModuleInterface;

/**
 * Module for FeedNotices.
 */
class Module implements ModuleInterface
{
    /**
     * OnInit of module.
     */
    public function onInit()
    {
        (new Provider())->register();
        (new Routes())->register();
    }
}
