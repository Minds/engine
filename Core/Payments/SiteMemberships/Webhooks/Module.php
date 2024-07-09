<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Webhooks;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    public function onInit()
    {
        (new Provider())->register();
        (new Routes())->register();
    }
}
