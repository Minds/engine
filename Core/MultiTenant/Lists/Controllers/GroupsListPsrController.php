<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Lists\Controllers;

use Minds\Core\MultiTenant\Lists\Services\TenantGroupsListService;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class GroupsListPsrController
{
    public function __construct(
        private readonly TenantGroupsListService $tenantGroupsListService
    ) {
    }

    public function getGroups(ServerRequestInterface $request): JsonResponse
    {
        return new JsonResponse([
            'data' => iterator_to_array($this->tenantGroupsListService->getGroups())
        ]);
    }
}
