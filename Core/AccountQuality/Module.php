<?php

namespace Minds\Core\AccountQuality;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public array $submodules = [];

    public function onInit()
    {
        (new Provider())->register();
        (new Routes())->register();
    }
}
