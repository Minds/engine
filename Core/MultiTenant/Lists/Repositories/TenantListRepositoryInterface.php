<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Lists\Repositories;

interface TenantListRepositoryInterface
{
    /**
     * @return iterable
     */
    public function getItems(): iterable;
}
