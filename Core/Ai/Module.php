<?php
declare(strict_types=1);

namespace Minds\Core\Ai;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * OnInit.
     * @throws ImmutableException
     */
    public function onInit(): void
    {
        (new Provider())->register();
        // (Di::_()->get(Events::class))->register();
    }
}
