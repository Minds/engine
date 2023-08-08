<?php

declare(strict_types=1);

namespace Minds\Core\ActivityPub;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * @return void
     * @throws \Minds\Core\Di\ImmutableException
     */
    public function onInit(): void
    {
        (new Provider())->register();
        (new Routes())->register();
    }
}
