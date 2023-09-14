<?php

declare(strict_types=1);

namespace Minds\Core\Settings;

use Minds\Core\Settings\GraphQL\GraphQLMappings;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    public function onInit(): void
    {
        (new Provider())->register();
        (new Routes())->register();
        (new GraphQLMappings())->register();
    }
}
