<?php
/**
 * CustomPages module.
 */

namespace Minds\Core\MultiTenant\CustomPages;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public function onInit()
    {
        $provider = new Provider();
        $provider->register();
        (new GraphQLMappings)->register();
    }
}
