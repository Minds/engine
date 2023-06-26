<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2;

use Minds\Core\Di\ImmutableException;

class Module implements \Minds\Interfaces\ModuleInterface
{

    /**
     * @inheritDoc
     * @throws ImmutableException
     */
    public function onInit(): void
    {
        (new Provider())->register();
    }
}
