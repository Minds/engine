<?php

namespace Minds\Core\Boost\V3\Partners;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public function onInit()
    {
        (new Provider())->register();
    }
}
