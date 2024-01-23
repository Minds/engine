<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Helpers;

use DateTimeImmutable;
use Exception;
use Minds\Common\Jwt;
use Minds\Core\Config\Config;

class GitlabPipelineJwtTokenValidator
{
    public function __construct(
        private readonly Jwt    $jwt,
        private readonly Config $config
    ) {
    }

    /**
     * @param string $token
     * @return bool
     * @throws Exception
     */
    public function checkToken(string $token): bool
    {
        $tokenOptions = $this->config->get('gitlab')['mobile']['pipeline']['jwt_token'];
        try {
            $claims = $this->jwt
                ->setKey($tokenOptions['secret_key'])
                ->decode($token);
            if ($claims['iss'] !== $tokenOptions['issuer'] || $claims['aud'][0] !== $tokenOptions['audience']) {
                return false;
            }

            if ($claims['exp'] < (new DateTimeImmutable())) {
                return false;
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
