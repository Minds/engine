<?php
declare(strict_types=1);

namespace Minds\Core\Helpdesk;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public array $submodules = [
        Zendesk\Module::class,
        Chatwoot\Module::class
    ];

    public function onInit()
    {
    }
}
