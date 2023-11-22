<?php

declare(strict_types=1);

namespace Minds\Core\Authentication\Oidc;

use Exception;
use GuzzleHttp\Client;
use Minds\Common\Cookie;
use Minds\Core\Authentication\Builders\Response\AuthenticationResponseBuilder;
use Minds\Core\Authentication\Exceptions\AuthenticationAttemptsExceededException;
use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Authentication\Oidc\Services\OidcAuthService;
use Minds\Core\Authentication\Oidc\Services\OidcProvidersService;
use Minds\Core\Authentication\Validators\AuthenticationRequestValidator;
use Minds\Core\Di\Di;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security\Exceptions\UserNotSetupException;
use Minds\Core\Security\TwoFactor\TwoFactorInvalidCodeException;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use RedisException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    public function __construct(
        private OidcAuthService $oidcAuthService,
        private OidcProvidersService $oidcProvidersService,
    ) {
    }

    /**
     * Frontend will call this endpoint to trigger the OIDC flow
     * 'http://localhost:8080/api/v3/authenticate/oidc/login'
     */
    public function oidcLogin(ServerRequest $request): Response
    {
        $queryParams = $request->getQueryParams();

        $providerId = $queryParams['providerId'] ?? null;
        // $returnUrl = $queryParams['returnUrl'] ?? 'http://localhost:8080/newsfeed/subscriptions/top';

        if (!$providerId) {
            return new HtmlResponse('Error: providerId must be provided');
        }

        $provider = $this->oidcProvidersService->getProviderById((int) $providerId);

        if (!$provider) {
            return new HtmlResponse('Error: provider not found');
        }

        // Set a cookie so that we remember the provider that is being used

        (new Cookie())
            ->setName('oidc_provider_id')
            ->setValue($providerId)
            ->setPath('/');

        // Set the Csrf State Token
        $csrfStateToken = hash('sha256', openssl_random_pseudo_bytes(128));
        
        (new Cookie())
            ->setName('oidc_csrf_state')
            ->setValue($csrfStateToken)
            ->setPath('/')
            ->setExpire(time() + 300) // Expire in 5 minutes
            ->setSecure(true)
            ->setHttpOnly(true)
            ->create();

        // Generate the authorization url

        $authUrl = $this->oidcAuthService->getAuthorizationUrl($provider, $csrfStateToken);

        return new RedirectResponse($authUrl);
    }

    /**
     * OIDC clients should callback to this endpoint
     */
    public function oidcCallback(ServerRequest $request): Response
    {
        $queryParams = $request->getQueryParams();

        if ($queryParams['error'] ?? null) {
            return new HtmlResponse('Error: ' . $queryParams['error_description']);
        }

        $providerId = $request->getCookieParams()['oidc_provider_id'] ?? null;

        if (!$providerId) {
            return new HtmlResponse('Error: Could not locate the oidc_provider_id cookie');
        }

        $provider = $this->oidcProvidersService->getProviderById((int) $providerId);

        $state = $queryParams['state'] ?? null;

        if (!$state) {
            return new HtmlResponse('Error: Not state token provided');
        }

        if ($state !== $request->getCookieParams()['oidc_csrf_state']) {
            return new HtmlResponse('Error: Invalid state token');
        }

        $code = $queryParams['code'] ?? null;

        $this->oidcAuthService->performAuthentication(
            provider: $provider,
            code: $code,
            state: $state,
        );

        $returnUrl = '/newsfeed/subscriptions/top';

        return new RedirectResponse($returnUrl);
    }

}
