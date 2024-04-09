<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Helpers;

use DateTimeImmutable;
use Exception;
use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;

class GitlabPipelineJwtTokenValidator
{
    private const ISSUER = 'https://gitlab.com/api/v4/projects/10171280/trigger/pipeline';

    public function __construct(
        private readonly Jwt                    $jwt,
        private readonly Config                 $config,
        private readonly MultiTenantBootService $multiTenantBootService,
    ) {
    }

    /**
     * @param string $token
     * @return bool
     * @throws Exception
     */
    public function checkToken(string $token, ?int $tenantId = null): bool
    {
        if (!$tenantId) {
            $tenantId = $this->config->get('tenant_id');
        }

        $this->multiTenantBootService->resetRootConfigs();

        $tokenOptions = $this->config->get('gitlab')['mobile']['pipeline']['jwt_token'];
        try {
            $claims = $this->jwt
                ->setKey($tokenOptions['secret_key'])
                ->decode($token);
            if ($claims['iss'] !== self::ISSUER || $claims['aud'][0] !== $this->config->get('site_url')) {
                $this->multiTenantBootService->bootFromTenantId($tenantId);
                return false;
            }

            if ($claims['exp'] < (new DateTimeImmutable())) {
                $this->multiTenantBootService->bootFromTenantId($tenantId);
                return false;
            }

            $this->multiTenantBootService->bootFromTenantId($tenantId);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
