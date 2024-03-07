<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Lists\Services;

use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Lists\Repositories\TenantListRepositoryInterface;

class TenantGroupsListService
{
    public function __construct(
        private readonly TenantListRepositoryInterface $tenantListRepository,
        private readonly EntitiesBuilder               $entitiesBuilder
    ) {
    }

    public function getGroups(): iterable
    {
        $groups = $this->tenantListRepository->getItems();

        foreach ($groups as $group) {
            yield [
                'entity' => $this->entitiesBuilder->single($group['guid'])?->export()
            ];
        }
    }
}
