<?php

namespace Minds\Core\Custom\Navigation;

use Minds\Core\Config\Config;
use Minds\Core\Custom\Navigation\Enums\NavigationItemActionEnum;
use Minds\Core\Custom\Navigation\Enums\NavigationItemTypeEnum;

class CustomNavigationService
{
    public function __construct(#
        private readonly Repository $repository,
        private readonly Config $config,
    ) {
    }

    /**
     * Returns the navigation items for the site
     * @return NavigationItem[]
     */
    public function getItems()
    {
        if ($this->isTenant()) {
            $defaults = $this->getDefaults();

            $configuredItems = $this->repository->getItems();

            $items = $this->mergeItems($defaults, $configuredItems);

            usort($items, function (NavigationItem $a, NavigationItem $b) {
                return $a->order > $b->order;
            });

            return $items;
        } else {
            return $this->getDefaults();
        }
    }

    /**
     * Adds a new item to the datastore
     */
    public function addItem(NavigationItem $item): bool
    {
        return $this->repository->addItem($item);
    }

    /**
     * Updates the order of the items
     * @param string[] $orderedItems
     */
    public function updateItemsOrder(array $orderedItems): bool
    {
        // When we update an item order, we also need to import default items that don't exist yet

        $items = $this->getItems();
        $itemsK = $this->toAssocArray($items);

        $this->repository->beginTransaction();

        try {
            foreach ($orderedItems as $order => $id) {
                if (isset($itemsK[$id])) {
                    $item = $itemsK[$id];
                    $item->order = $order;

                    $this->repository->addItem($item);
                }
            }
            $this->repository->commitTransaction();
        } catch (\Exception $e) {
            $this->repository->rollbackTransaction();
            return false;
        }

        return true;
    }

    /**
     * @return NavigationItem[]
     */
    private function mergeItems($a, $b): array
    {
        $a = $this->toAssocArray($a);
        $b = $this->toAssocArray($b);

        foreach ($b as $id => $item) {
            $a[$id] = $item;
        }

        return array_values($a);
    }

    /**
     * Convert the array to an associative array, with the 'id' as the key
     * @return array[string]NavigationItem
     */
    private function toAssocArray($array): array
    {
        $return = [];
        foreach ($array as $item) {
            $return[$item->id] = $item;
        }
        return $return;
    }

    /**
     * @return NavigationItem[]
     */
    private function getDefaults(): array
    {
        return [
            new NavigationItem(
                id: 'newsfeed',
                name: 'Newsfeed',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                iconId: 'home',
                path: '/newsfeed',
                order: 1,
            ),
            new NavigationItem(
                id: 'explore',
                name: 'Explore',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                iconId: 'explore',
                path: '/explore',
                order: 2,
            ),
            new NavigationItem(
                id: 'groups',
                name: 'Groups',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                iconId: 'group',
                path: '/groups',
                url: null,
                action: null,
                order: 3,
            ),
            new NavigationItem(
                id: 'admin',
                name: 'Admin',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                iconId: 'admin_panel_settings',
                path: '/admin',
                order: 4
            ),
            new NavigationItem(
                id: 'channel',
                name: '', // Clients should set this
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                iconId: '',
                order: 5,
            ),
            new NavigationItem(
                id: 'more',
                name: 'More',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                iconId: 'more_horiz',
                action: NavigationItemActionEnum::SHOW_SIDEBAR_MORE,
                order: 6
            ),
        ];
    }

    /**
     * True/False if tenant or not
     */
    private function isTenant(): bool
    {
        return !!$this->config->get('tenant_id');
    }
}
