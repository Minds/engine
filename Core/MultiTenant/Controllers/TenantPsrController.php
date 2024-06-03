<?php
namespace Minds\Core\MultiTenant\Controllers;

use Minds\Core\MultiTenant\Services\AutoTrialService;
use Minds\Core\Router\Enums\RequestAttributeEnum;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
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
        // We check the admin property directly as the ->isAdmin function checks for the ip
        // whitelist, but we might be calling this from elsewhere

        /** @var User */
        $user = $request->getAttribute(RequestAttributeEnum::USER);

        if ($user->get('admin') !== 'yes') {
            throw new ForbiddenException();
        }

        $payload = $request->getParsedBody();

        $email = $payload['email'];

        $tenant = $this->trialService->startTrialWithEmail($email);

        return new JsonResponse([
            'tenant_id' => $tenant->id,
        ]);
    }
}
