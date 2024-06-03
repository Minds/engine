<?php
namespace Minds\Core\MultiTenant\Controllers;

use Minds\Core\MultiTenant\Services\AutoTrialService;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class TenantPsrController
{
    public function __construct(
        private AutoTrialService $trialService,
    ) {
        
    }

    public function startTrial(ServerRequestInterface $request): JsonResponse
    {
        $payload = $request->getParsedBody();

        $email = $payload['email'];

        $tenant = $this->trialService->startTrialWithEmail($email);

        return new JsonResponse([
            'tenant_id' => $tenant->id,
        ]);
    }
}
