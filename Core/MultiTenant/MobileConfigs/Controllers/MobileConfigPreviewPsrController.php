<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Controllers;

use Minds\Core\MultiTenant\MobileConfigs\Helpers\GitlabPipelineJwtTokenValidator;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigManagementService;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class MobileConfigPreviewPsrController
{
    public function __construct(
        private readonly MobileConfigManagementService   $mobileConfigManagementService,
        private readonly GitlabPipelineJwtTokenValidator $gitlabPipelineJwtTokenValidator,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ForbiddenException
     */
    public function processMobilePreviewWebhook(ServerRequestInterface $request): JsonResponse
    {
        $jwtToken = $request->getHeader('token');
        if (!$this->gitlabPipelineJwtTokenValidator->checkToken($jwtToken[0])) {
            throw new ForbiddenException('Invalid token');
        }

        ['TENANT_ID' => $tenantId, 'status' => $status, 'VERSION' => $appVersion] = $request->getParsedBody();

        $this->mobileConfigManagementService->processMobilePreviewWebhook(
            tenantId: (int)$tenantId,
            appVersion: $appVersion,
            status: $status
        );

        return new JsonResponse("", 200);
    }
}
