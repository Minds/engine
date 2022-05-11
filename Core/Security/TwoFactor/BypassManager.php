<?php

namespace Minds\Core\Security\TwoFactor;

use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;

/**
 * Handles TwoFactor bypass for E2E.
 */
class BypassManager
{
    /**
     * BypassManager constructor.
     * @param ?Config $config - config class instance.
     * @param ?Jwt $jwt - jwt lib for decoding.
     * @param ?Logger $logger - logger class instance.
     */
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
     * Verify bypassing TwoFactor
     * @param string $code - code to check.
     * @return bool - true if bypass is permitted.
     */
    public function verify(string $code): bool
    {
        if (!isset($_COOKIE['two_factor_bypass'])) {
            return false;
        }

        $bypassKey = $this->config->get('captcha')['bypass_key'];

        $decoded = $this->jwt
            ->setKey($bypassKey)
            ->decode($_COOKIE['two_factor_bypass']);

        $inputted = $decoded['data'];

        $this->logger->warn('[TwoFactor]: Bypass cookie was used');

        return $inputted == $code;
    }
}
