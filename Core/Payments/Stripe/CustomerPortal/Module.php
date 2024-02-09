<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\CustomerPortal;

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
    }
}
