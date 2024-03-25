<?php

namespace Minds\Core\Custom\Navigation\Controllers;

use Minds\Core\Custom\Navigation\CustomNavigationService;
use Minds\Core\Custom\Navigation\Enums\NavigationItemActionEnum;
use Minds\Core\Custom\Navigation\Enums\NavigationItemTypeEnum;
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
     * Add or update a navigation item
     */
    #[Mutation]
    public function upsertCustomNavigationItem(
        string $id,
        string $name,
        NavigationItemTypeEnum $type,
        bool $visible,
        string $iconId,
        string $path = null,
        string $url = null,
        NavigationItemActionEnum $action = null,
        int $order = 500,
    ): NavigationItem {

        $item = new NavigationItem(
            id: $id,
            name: $name,
            type: $type,
            visible: $visible,
            iconId: $iconId,
            path: $path,
            url: $url,
            action: $action,
            order: $order,
        );

        if ($this->service->addItem($item)) {
            return $item;
        } else {
            throw new ServerErrorException();
        }
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
