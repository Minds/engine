<?php
declare(strict_types=1);

namespace Minds\Core\GraphQL;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * OnInit.
     */
    public function onInit(): void
    {
        $provider = new Provider();
        $provider->register();
        (new Routes())->register();
        (new GraphQLMappings)->register();
    }
}
