<?php

namespace Minds\Core\Custom\Navigation\Controllers;

use Minds\Core\Custom\Navigation\CustomNavigationService;
use Minds\Core\Custom\Navigation\NavigationItem;
use Minds\Exceptions\ServerErrorException;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class NavigationController
{
    public function __construct(
        private CustomNavigationService $service,
    ) {
        
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

    /**
     * Updates the order of the navigation items
     * @param string[] $orderedIds
     * @return NavigationItem[]
     */
    #[Mutation]
    public function updateCustomNavigationItemsOrder(array $orderedIds): array
    {
        if (!$this->service->updateItemsOrder($orderedIds)) {
            throw new ServerErrorException();
        }

        return $this->service->getItems();
    }
}
