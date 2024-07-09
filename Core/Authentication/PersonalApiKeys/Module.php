<?php

namespace Minds\Core\Authentication\PersonalApiKeys;

use Minds\Core\Di\ImmutableException;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * @inheritDoc
     * @throws ImmutableException
     */
    public function onInit()
    {
        (new Provider())->register();
        (new GraphQLMappings())->register();
    }
}
