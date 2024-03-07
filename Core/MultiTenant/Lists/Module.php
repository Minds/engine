<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Lists;

use Minds\Core\Di\ImmutableException;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * @inheritDoc
     * @throws ImmutableException
     */
    public function onInit(): void
    {
        (new Provider())->register();
        (new Routes())->register();
    }
}
