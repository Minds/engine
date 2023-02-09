<?php
declare(strict_types=1);

namespace Minds\Core\Demonetization;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public function onInit()
    {
        (new Provider())->register();
    }
}
