<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Insights;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * @return void
     */
    public function onInit()
    {
        (new Provider())->register();
        (new Routes())->register();
    }
}
