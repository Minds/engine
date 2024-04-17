<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Webhooks;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{

    /**
     * @inheritDoc
     */
    public function onInit(): void
    {
        (new Provider())->register();
    }
}
