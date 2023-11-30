<?php
namespace Minds\Core\MultiTenant\AutoLogin;

use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    public function __construct(
        private AutoLoginService $autoLoginService,
    ) {
        
    }

    /**
     * Generates a login url for a tenant site
     */
    public function generate(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $loggedInUser = $request->getAttribute('_user');
    
        if (!$loggedInUser) {
            throw new ForbiddenException();
        }

        $tenantId = (int) $request->getParsedBody()['tenant_id'];

        $loginUrl = $this->autoLoginService->buildLoginUrl(
            tenantId: $tenantId,
            loggedInUser: $loggedInUser
        );
    
        return new JsonResponse([
            'login_url' => $loginUrl,
        ]);
    }

    /**
     * To be called on a tenant site, generates a login link
     */
    public function login(ServerRequest $request): RedirectResponse
    {
        $jwtToken = $request->getQueryParams()['jwtToken'];

        $this->autoLoginService->performLogin($jwtToken);

        return new RedirectResponse('/');
    }

}
