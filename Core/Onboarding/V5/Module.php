<?php
declare(strict_types=1);

namespace Minds\Core\Onboarding\V5;

use Minds\Core\Onboarding\V5\GraphQL\GraphQLMappings;
use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * OnInit.
     */
    public function onInit()
    {
        (new Provider())->register();
        (new GraphQLMappings())->register();
    }
}
