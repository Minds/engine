<?php

namespace Minds\Core\Media\Proxy;

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
