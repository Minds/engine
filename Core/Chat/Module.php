<?php
declare(strict_types=1);

namespace Minds\Core\Chat;

use Minds\Core\Di\ImmutableException;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * OnInit.
     * @throws ImmutableException
     */
    public function onInit(): void
    {
        (new Provider())->register();
        (new GraphQLMappings())->register();
    }
}
