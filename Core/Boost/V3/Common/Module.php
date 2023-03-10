<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Common;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * @return void
     */
    public function onInit()
    {
        (new Provider())->register();
    }
}
