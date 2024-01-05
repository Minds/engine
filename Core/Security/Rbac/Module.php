<?php
/**
 * Permissions module.
 */

namespace Minds\Core\Security\Rbac;

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
