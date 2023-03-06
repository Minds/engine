<?php
declare(strict_types=1);

namespace Minds\Core\Email\Mautic;

use Minds\Core\Di\ImmutableException;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public array $submodules = [
    ];
    
    /**
     * @return void
     * @throws ImmutableException
     */
    public function onInit()
    {
        (new Provider())->register();
    }
}
