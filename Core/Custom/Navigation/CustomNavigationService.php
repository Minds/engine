<?php

namespace Minds\Core\Custom\Navigation;

use Minds\Core\Config\Config;
use Minds\Core\Custom\Navigation\Enums\NavigationItemActionEnum;
use Minds\Core\Custom\Navigation\Enums\NavigationItemTypeEnum;
use Minds\Core\Data\cache\PsrWrapper;

class CustomNavigationService
{
    public function __construct(#
        private readonly Repository $repository,
        private readonly PsrWrapper $cache,
        private readonly Config $config,
    ) {
    }

    /**
     * Returns the navigation items for the site
     * @return NavigationItem[]
     */
    public function getItems(bool $useCache = true)
    {
        if ($this->isTenant()) {
            $defaults = $this->getDefaults();

            if ($useCache && $cachedItems = $this->cache->get($this->getCacheKey())) {
                $configuredItems = unserialize($cachedItems);
            } else {
                $configuredItems = $this->repository->getItems();

                $this->cache->set($this->getCacheKey(), serialize($configuredItems));
            }

            $items = $this->mergeItems($defaults, $configuredItems);

            usort($items, function (NavigationItem $a, NavigationItem $b) {
                return $a->order <=> $b->order;
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
        $result = $this->repository->addItem($item);

        $this->cache->delete($this->getCacheKey());

        return $result;
    }

    /**
     * Remove an item from the datastore
     */
    public function deleteItem(string $id): bool
    {
        $result = $this->repository->deleteItem($id);

        $this->cache->delete($this->getCacheKey());

        return $result;
    }

    /**
     * Updates the order of the items
     * @param string[] $orderedItems
     */
    public function updateItemsOrder(array $orderedItems): bool
    {
        // When we update an item order, we also need to import default items that don't exist yet

        $items = $this->getItems(useCache: false);
        $itemsK = $this->toAssocArray($items);

        $this->repository->beginTransaction();

        try {
            foreach ($orderedItems as $order => $id) {
                if (isset($itemsK[$id])) {
                    $item = $itemsK[$id];
                    $item->order = $order;

                    $this->addItem($item);
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
                visibleMobile: true,
                iconId: 'home',
                path: '/newsfeed',
                order: 1,
            ),
            new NavigationItem(
                id: 'explore',
                name: 'Explore',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: true,
                iconId: 'tag',
                path: '/discovery',
                order: 2,
            ),
            new NavigationItem(
                id: 'boost',
                name: 'Boost',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: false,
                iconId: 'trending_up',
                path: '/boost/boost-console',
                order: 3,
            ),
            new NavigationItem(
                id: 'groups',
                name: 'Groups',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: true,
                iconId: 'group',
                path: '/groups',
                url: null,
                action: null,
                order: 4,
            ),
            new NavigationItem(
                id: 'chat',
                name: 'Chat',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: true,
                iconId: 'chat_bubble',
                path: '/chat/rooms',
                url: null,
                action: null,
                order: 5,
            ),
            new NavigationItem(
                id: 'memberships',
                name: 'Memberships',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: false,
                iconId: 'verified',
                path: '/memberships',
                url: null,
                action: null,
                order: 6,
            ),
            new NavigationItem(
                id: 'admin',
                name: 'Admin',
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: false,
                iconId: 'dashboard',
                path: '/network/admin',
                order: 7
            ),
            new NavigationItem(
                id: 'channel',
                name: '', // Clients should set this
                type: NavigationItemTypeEnum::CORE,
                visible: true,
                visibleMobile: true,
                iconId: '',
                order: 8,
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

    private function getCacheKey(): string
    {
        return 'custom-nav';
    }
}
