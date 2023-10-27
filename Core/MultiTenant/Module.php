<?php
namespace Minds\Core\MultiTenant;

use Minds\Core\Di\ImmutableException;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /** @var array */
    public $submodules = [
        Configs\Module::class,
    ];

    /**
     * OnInit
     * @throws ImmutableException
     */
    public function onInit(): void
    {
        (new GraphQLMappings())->register();
        (new Provider())->register();
    }
}
