<?php
declare(strict_types=1);

namespace Minds\Core\Monetization;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public array $submodules = [
        Demonetization\Module::class
    ];

    public function onInit()
    {
    }
}
