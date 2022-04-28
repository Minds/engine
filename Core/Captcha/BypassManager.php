<?php

namespace Minds\Core\Captcha;

use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;

/**
 * Handles CAPTCHA bypass for E2E.
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
     * Verify bypassing a captcha
     * @param string $captchaString - captcha string to check.
     * @return bool - true if bypass is permitted.
     */
    public function verify(string $captchaText): bool
    {
        if (!isset($_COOKIE['captcha_bypass'])) {
            return false;
        }

        $bypassKey = $this->config->get('captcha')['bypass_key'];

        $decoded = $this->jwt
            ->setKey($bypassKey)
            ->decode($_COOKIE['captcha_bypass']);

        $inputted = $decoded['data'];

        $this->logger->warn('[Captcha]: Bypass cookie was used');

        return $inputted == $captchaText;
    }
}
