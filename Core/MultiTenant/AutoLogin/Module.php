<?php

namespace Minds\Core\MultiTenant\AutoLogin;

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
    }
}
