<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships;

use Minds\Core\Di\ImmutableException;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public array $submodules  = [
        PaywalledEntities\Module::class,
    ];

    /**
     * @return void
     * @throws ImmutableException
     */
    public function onInit(): void
    {
        (new GraphQLMappings())->register();
        (new Provider())->register();
        (new Routes())->register();
    }
}
