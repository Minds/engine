<?php
namespace Minds\Core\MultiTenant\AutoLogin;

use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\Data\cache\Cassandra;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security\XSRF;
use Minds\Core\Session;
use Minds\Core\Sessions\Manager as SessionsManager;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;

class AutoLoginService
{
    public function __construct(
        private EntitiesBuilder $entitiesBuilder,
        private SessionsManager $sessionsManager,
        private MultiTenantDataService $tenantDataService,
        private DomainService $tenantDomainService,
        private Cassandra $tmpStore,
        private Jwt $jwt,
        private Config $config,
    ) {
        
    }

    /**
     * Generate a url for autologin
     */
    public function buildLoginUrl(
        int $tenantId,
        User $loggedInUser
    ): string {
        $tenant = $this->tenantDataService->getTenantFromId($tenantId);

        if (!$tenant) {
            throw new NotFoundException("Could not find tenant");
        }

        $domain = $this->tenantDomainService->buildDomain($tenant);

        if ($tenant->ownerGuid !== (int) $loggedInUser->getGuid()) {
            throw new ForbiddenException("Current user does not have ownership of the tenant");
        }

        $ssoToken = $this->jwt->randomString();
        
        // Expire in 60 seconds
        $expires = time() + 60;

        // Store the sso token server side to verify at a later date
        $this->tmpStore->set('multi-tenant-autologin:' . $ssoToken, true, 60);

        $jwtToken = $this->jwt
            ->setKey($this->getEncryptionKey())
            ->encode(
                payload: [
                    'user_guid' => $tenant->rootUserGuid,
                    'tenant_id' => $tenantId,
                    'sso_token' => $ssoToken,
                ],
                exp: $expires,
                nbf: time()
            );

        return "https://$domain/api/v3/multi-tenant/auto-login/login?jwtToken=$jwtToken";
    }

    /**
     * Validate the jwt token and do the login
     */
    public function performLogin(string $jwtToken): void
    {
        $jwtTokenData = $this->jwt
            ->setKey($this->getEncryptionKey())
            ->decode($jwtToken);

        $userGuid = $jwtTokenData['user_guid'];
        $tenantId = $jwtTokenData['tenant_id'];
        $ssoToken = $jwtTokenData['sso_token'];

        // Wrong tenant, how did that happen?
        if ($tenantId !== $this->config->get('tenant_id')) {
            throw new UnauthorizedException('Tenant does not match');
        }

        // The sso token should be available in the temporary store
        if (!$this->tmpStore->get('multi-tenant-autologin:' . $ssoToken)) {
            throw new UnauthorizedException();
        }

        // Delete the tmp key
        $this->tmpStore->delete('multi-tenant-autologin:' . $ssoToken);

        $user = $this->entitiesBuilder->single($userGuid);

        $this->sessionsManager->setUser($user);
        $this->sessionsManager->createSession();
        $this->sessionsManager->save(); // save to db and cookie

        // Terrible hack, but there is no way to inject this right now
        if ($jwtToken !== 'jwt-token-testing') {
            \set_last_login($user);

            Session::generateJWTCookie($this->sessionsManager->getSession());
            XSRF::setCookie(true);
        }
    }

    private function getEncryptionKey(): string
    {
        return $this->config->get('oauth')['encryption_key'] ?? '';
    }

}
