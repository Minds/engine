<?php
namespace Minds\Core\Security\TOTP;

use Minds\Traits\MagicAttributes;

/**
 * TOTP Secret
 * @package Minds\Core\Security\TOTP
 * @method string getSecret()
 * @method self setSecret(string $secret)
 * @method string getUserGuid()
 * @method self setUserGuid(string $userGuid)
 * @method string getDeviceId()
 * @method self setDeviceId(string $deviceId)
 * @method string getrecoveryHash()
 * @method self setrecoveryHash(string $recoveryHash)
 */
class TOTPSecret
{
    use MagicAttributes;

    /** @var string */
    protected $secret;

    /** @var string */
    protected $userGuid;

    /** @var string */
    protected $deviceId;

    /** @var string */
    protected $recoveryHash;

    /**
     * Public export for secret
     * @param array $extras
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'secret' => $this->secret,
            'user_guid' => $this->userGuid,
            'device_id' => $this->deviceId,
            'recovery_hash' => $this->recoveryHash,
        ];
    }
}
