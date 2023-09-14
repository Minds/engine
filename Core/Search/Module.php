<?php
declare(strict_types=1);

namespace Minds\Core\Search;

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
        (new Provider)->register();
        (new GraphQLMappings())->register();
    }
}
