<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Exception;
use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Services\MobileConfigManagementService;
use Minds\Core\MultiTenant\Traits\MobilePreviewJwtTokenTrait;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Exceptions\ServerErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class MobileConfigPreviewPsrController
{
    use MobilePreviewJwtTokenTrait;

    public function __construct(
        private readonly MobileConfigManagementService $mobileConfigManagementService,
        private readonly Jwt                           $jwt,
        private readonly Config                        $config
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ForbiddenException
     * @throws ServerErrorException
     * @throws Exception
     */
    public function processMobilePreviewWebhook(ServerRequestInterface $request): JsonResponse
    {
        $jwtToken = $request->getHeader('token');
        if (!$this->checkToken($jwtToken[0])) {
            throw new ForbiddenException('Invalid token');
        }

        ['TENANT_ID' => $tenantId, 'status' => $status] = $request->getParsedBody();

        $this->mobileConfigManagementService->processMobilePreviewWebhook(
            tenantId: (int)$tenantId,
            status: $status
        );

        return new JsonResponse("", 200);
    }
}
