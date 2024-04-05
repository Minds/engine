<?php

namespace Minds\Core\Analytics\PostHog;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * OnInit
     */
    public function onInit()
    {
        (new Provider())->register();
        (new GraphQLMappings())->register();
    }
}
