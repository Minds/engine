<?php

namespace Minds\Core\Custom\Navigation\Controllers;

use Minds\Core\Custom\Navigation\CustomNavigationService;
use Minds\Core\Custom\Navigation\Enums\NavigationItemActionEnum;
use Minds\Core\Custom\Navigation\Enums\NavigationItemTypeEnum;
use Minds\Core\Custom\Navigation\NavigationItem;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;

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
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function upsertCustomNavigationItem(#
        #[InjectUser] User $loggedInUser,
        string $id,
        string $name,
        NavigationItemTypeEnum $type,
        bool $visible,
        bool $visibleMobile,
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
            visibleMobile: $visibleMobile,
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
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function updateCustomNavigationItemsOrder(array $orderedIds, #[InjectUser] User $loggedInUser): array
    {
        if (!$this->service->updateItemsOrder($orderedIds)) {
            throw new ServerErrorException();
        }

        return $this->service->getItems();
    }

    /**
     * Deletes a navigation item
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function deleteCustomNavigationItem(string $id, #[InjectUser] User $loggedInUser): bool
    {
        return $this->service->deleteItem($id);
    }
}
