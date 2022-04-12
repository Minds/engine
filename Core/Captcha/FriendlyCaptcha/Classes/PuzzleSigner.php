<?php

namespace Minds\Core\Captcha\FriendlyCaptcha\Classes;

use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\MisconfigurationException;
use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\SignatureMismatchException;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

/**
 * Puzzle signer - handles the singing of puzzle buffers.
 */
class PuzzleSigner
{
    /** @var string secret for signing. */
    private string $secret = '';
 
    /**
     * PuzzleSigner constructor.
     * @param ?Config $config - config service.
     * @throws MisconfigurationException - if FriendlyCaptcha secret is not set.
     */
    public function __construct(
        ?Config $config = null
    ) {
        $config ??= Di::_()->get('Config');

        if (!$this->secret = $config->get('captcha')['friendly_captcha']['signing_secret'] ?? false) {
            throw new MisconfigurationException('FriendlyCaptcha secret not set');
        }
    }

    /**
     * Verify a puzzle solution.
     * @param PuzzleSolution $puzzleSolution - puzzle solution object to verify.
     * @throws SignatureMismatchException - if signature does not match.
     * @return boolean - true if success, will throw on failure.
     */
    public function verify(PuzzleSolution $puzzleSolution): bool
    {
        $serverSignature = $this->sign(
            $puzzleSolution->getPuzzle()->as('binary')
        );
        if ($serverSignature !== $puzzleSolution->getPuzzle()->getSignature()) {
            throw new SignatureMismatchException();
        }
        return true;
    }

    /**
     * Sign a puzzle buffer using secret.
     * @param string $buffer - puzzle buffer to sign.
     * @return string - signed puzzle buffer.
     */
    public function sign(string $buffer): string
    {
        return hash_hmac('sha256', $buffer, $this->secret);
    }
}
