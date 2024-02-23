<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Lists\Services;

use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Lists\Repositories\TenantListRepositoryInterface;
use Minds\Entities\User;

class TenantChannelsListService
{
    public function __construct(
        private readonly TenantListRepositoryInterface $tenantListRepository,
        private readonly EntitiesBuilder               $entitiesBuilder
    ) {
    }

    public function getChannels(
        User $loggedInUser
    ): iterable {
        $items = $this->tenantListRepository->getItems();

        foreach ($items as $item) {
            if ($item['guid'] == $loggedInUser->getGuid()) {
                continue;
            }

            yield [
                'entity' => $this->entitiesBuilder->single($item['guid'])?->export()
            ];
        }
    }
}
