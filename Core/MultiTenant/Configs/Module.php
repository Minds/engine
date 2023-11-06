<?php

namespace Minds\Core\MultiTenant\Configs;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * OnInit
     */
    public function onInit()
    {
        (new Provider())->register();
        (new Routes())->register();
        (new GraphQLMappings())->register();
    }
}
