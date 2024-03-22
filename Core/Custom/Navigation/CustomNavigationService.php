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
     * Update an existing item with new information
     */
    public function updateItem(NavigationItem $item): bool
    {
        return $this->repository->updateItem($item);
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
            ),
            new NavigationItem(
                id: 'explore',
                name: 'Explore',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                iconId: 'explore',
                path: '/explore',
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
            ),
            new NavigationItem(
                id: 'admin',
                name: 'Admin',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                iconId: 'admin_panel_settings',
                path: '/admin',
            ),
            new NavigationItem(
                id: 'channel',
                name: '', // Clients should set this
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                iconId: '',
            ),
            new NavigationItem(
                id: 'more',
                name: 'More',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                iconId: 'more_horiz',
                action: NavigationItemActionEnum::SHOW_SIDEBAR_MORE
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
