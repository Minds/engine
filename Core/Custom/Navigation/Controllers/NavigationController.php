<?php

namespace Minds\Core\Custom\Navigation\Controllers;

use Minds\Core\Custom\Navigation\CustomNavigationService;
use Minds\Core\Custom\Navigation\NavigationItem;
use TheCodingMachine\GraphQLite\Annotations\Query;

class NavigationController
{
    public function __construct(
        private CustomNavigationService $service,
    )
    {
        
    }

    /**
     * Returns the navigation items that are configured for a site
     * @return NavigationItem[]
     */
    #[Query]
    public function getCustomNavigationItems(): array
    {
        return $this->service->getItems();
    }
}
