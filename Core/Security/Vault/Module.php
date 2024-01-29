<?php
/**
 * Hashicorp Vault integration
 */

namespace Minds\Core\Security\Vault;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public function onInit()
    {
        $provider = new Provider();
        $provider->register();
    }
}
