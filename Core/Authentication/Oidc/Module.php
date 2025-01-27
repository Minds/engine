<?php

namespace Minds\Core\Authentication\Oidc;

use Minds\Core\Di\Di;
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
        (new Routes())->register();
        (new GraphQLMappings())->register();
        (Di::_()->get(Events::class))->register();
    }
}
