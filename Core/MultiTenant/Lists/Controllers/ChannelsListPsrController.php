<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Lists\Controllers;

use Minds\Core\MultiTenant\Lists\Services\TenantChannelsListService;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class ChannelsListPsrController
{
    public function __construct(
        private readonly TenantChannelsListService $tenantChannelsListService
    ) {
    }

    public function getChannels(ServerRequestInterface $request): JsonResponse
    {
        return new JsonResponse([
            'data' => iterator_to_array($this->tenantChannelsListService->getChannels($request->getAttribute('_user')))
        ]);
    }
}
