<?php

namespace Minds\Core\MultiTenant\Billing;

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
