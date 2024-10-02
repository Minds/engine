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
        $redirectPath = $this->getSanitizedRedirectPath(
            $request->getParsedBody()['redirect_path'] ?? null
        );

        $this->autoLoginService->performLogin($jwtToken);

        return new RedirectResponse($redirectPath);
    }

    
    /**
     * Performs the login, from GET request query param
     */
    public function getLogin(ServerRequest $request): RedirectResponse
    {
        $jwtToken = $request->getQueryParams()['token'];
        $redirectPath = $this->getSanitizedRedirectPath(
            $request->getQueryParams()['redirect_path'] ?? null
        );

        $this->autoLoginService->performLogin($jwtToken);

        return new RedirectResponse($redirectPath);
    }

    /**
     * Gets sanitized redirect path.
     * @param string|null $redirectPath - the path to sanitize.
     * @return string - the redirect path.
     */
    private function getSanitizedRedirectPath(?string $redirectPath): string
    {
        return $redirectPath && str_starts_with($redirectPath, '/') ?
            $redirectPath :
            '/network/admin';
    }
}
