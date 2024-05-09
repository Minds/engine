<?php

namespace Minds\Core\Authentication;

use Minds\Core\Di\ImmutableException;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /** @var array $submodules */
    public $submodules = [
        Oidc\Module::class,
        PersonalApiKeys\Module::class,
    ];

    /**
     * @inheritDoc
     * @throws ImmutableException
     */
    public function onInit()
    {
        (new Provider())->register();
        (new Routes())->register();
    }
}
