<?php

declare(strict_types=1);

namespace Minds\Core\Authentication\Oidc\Controllers;

use Minds\Common\Cookie;
use Minds\Core\Authentication\Oidc\Services\OidcAuthService;
use Minds\Core\Authentication\Oidc\Services\OidcProvidersService;
use Minds\Core\Authentication\Oidc\Services\OidcUserService;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\ServerRequest;

class OidcPsr7Controller
{
    public function __construct(
        private OidcAuthService $oidcAuthService,
        private OidcProvidersService $oidcProvidersService,
        private OidcUserService $oidcUserService,
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
            ->setPath('/')
            ->create();

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

        return new RedirectResponse($authUrl, 302, [
            'Cache-Control' => 'no-cache',
            'X-No-Cache' => 1,
            'X-MINDS-TEST' => 2,
        ]);
    }

    /**
     * OIDC clients should callback to this endpoint
     */
    public function oidcCallback(ServerRequest $request): Response
    {
        $queryParams = $request->getQueryParams();

        if ($queryParams['error'] ?? null) {
            throw new UserErrorException($queryParams['error_description']);
        }

        $providerId = $request->getCookieParams()['oidc_provider_id'] ?? null;

        if (!$providerId) {
            throw new UserErrorException('Error: Could not locate the oidc_provider_id cookie');
        }

        $provider = $this->oidcProvidersService->getProviderById((int) $providerId);

        $state = $queryParams['state'] ?? null;

        if (!$state) {
            throw new UserErrorException('Error: Not state token provided');
        }

        if ($state !== $request->getCookieParams()['oidc_csrf_state']) {
            throw new UserErrorException('Error: Invalid state token');
        }

        $code = $queryParams['code'] ?? null;

        $this->oidcAuthService->performAuthentication(
            provider: $provider,
            code: $code,
            state: $state,
        );

        return new HtmlResponse(
            <<<HTML
<script>window.close();</script>
<p>Please close this window/tab.</p>
HTML
        );
    }

    /**
     * Will disable a user
     */
    public function suspendUser(ServerRequest $request): Response
    {
        $sub = $request->getAttribute('parameters')['sub'];
        $providerId = (int) $request->getAttribute('parameters')['providerId'];

        try {
            $this->oidcUserService->suspendUserFromSub($sub, $providerId);
        } catch (NotFoundException $e) {
            // If zapier, still return a 200 status code
            if ($request->getHeader('User-Agent') !== 'Zapier') {
                throw $e;
            }
        }

        return new JsonResponse([]);
    }

}
