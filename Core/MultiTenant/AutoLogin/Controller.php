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
    public function getLoginUrl(ServerRequest $request): JsonResponse
    {
        /** @var User $loggedInUser */
        $loggedInUser = $request->getAttribute('_user');
    
        if (!$loggedInUser) {
            throw new ForbiddenException();
        }

        $tenantId = (int) $request->getQueryParams()['tenant_id'];

        $loginUrl = $this->autoLoginService->buildLoginUrl(
            tenantId: $tenantId
        );

        $jwtToken = $this->autoLoginService->buildJwtToken(
            tenantId: $tenantId,
            loggedInUser: $loggedInUser
        );

        return new JsonResponse([
            'login_url' => $loginUrl,
            'jwt_token' => $jwtToken
        ]);
    }

    /**
     * Performs the login, from a post body
     */
    public function postLogin(ServerRequest $request): RedirectResponse
    {
        $jwtToken = $request->getParsedBody()['jwt_token'];

        $this->autoLoginService->performLogin($jwtToken);

        return new RedirectResponse('/network/admin');
    }

    
    /**
     * Performs the login, from GET request query param
     */
    public function getLogin(ServerRequest $request): RedirectResponse
    {
        $jwtToken = $request->getQueryParams()['token'];

        $this->autoLoginService->performLogin($jwtToken);

        return new RedirectResponse('/network/admin');
    }

}
