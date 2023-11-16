<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2;

use Minds\Core\Di\ImmutableException;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function onInit(): void
    {
        (new Provider())->register();
        (new GraphQLMappings())->register();
    }
}
