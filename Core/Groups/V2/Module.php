<?php
declare(strict_types=1);

namespace Minds\Core\Groups\V2;

use Minds\Core\Groups\V2\GraphQL\GraphQLMappings;
use Minds\Core\Di\ImmutableException;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function onInit()
    {
        (new GraphQLMappings())->register();
    }
}
