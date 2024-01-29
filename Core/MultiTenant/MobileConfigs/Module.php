<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    public function onInit(): void
    {
        (new Provider())->register();
        (new GraphQLMappings())->register();
        (new Routes())->register();
    }
}
