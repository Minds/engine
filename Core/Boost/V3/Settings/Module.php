<?php

declare(strict_types=1);

namespace Minds\Core\Boost\V3\Settings;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * @return void
     */
    public function onInit(): void
    {
        (new Provider())->register();
        (new Routes())->register();
    }
}
