<?php

namespace Minds\Core\Security\TwoFactor\Bypass;

use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;

/**
 * Handles MFA bypass for E2E.
 */
class Manager
{
    public function __construct(
        private ?Config $config = null,
        private ?Jwt $jwt = null,
        private ?Logger $logger = null
    ) {
        $this->config ??= Di::_()->get('Config');
        $this->jwt ??= new Jwt();
        $this->logger ??= Di::_()->get('Logger');
    }
    /**
     * Verify bypassing MFA
     * @param string $code - entered code.
     * @return bool - true if bypass is permitted.
     */
    public function canBypass(string $code): bool
    {
        if (!isset($_COOKIE['two_factor_bypass'])) {
            return false;
        }

        $bypassKey = $this->config->get('captcha')['bypass_key'];

        $decoded = $this->jwt
            ->setKey($bypassKey)
            ->decode($_COOKIE['two_factor_bypass']);

        $inputted = $decoded['data'];

        $this->logger->warn('[2FA]: Bypass cookie was used');

        return $inputted == $code;
    }
}
