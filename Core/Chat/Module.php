<?php
declare(strict_types=1);

namespace Minds\Core\Chat;

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

        $graphQLMappings = new GraphQLMappings();
        $graphQLMappings->register();
    }
}
