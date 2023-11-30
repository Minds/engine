<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public function onInit()
    {
        (new Provider())->register();
        (new GraphQLMappings())->register();
    }
}
