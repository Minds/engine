<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Minds\Core\Di\ImmutableException;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public array $submodules = [
        Common\Module::class,
        Summaries\Module::class,
        Ranking\Module::class,
        Insights\Module::class,
    ];

    /**
     * @return void
     * @throws ImmutableException
     */
    public function onInit()
    {
        (new Provider())->register();
        (new Routes())->register();
    }
}
