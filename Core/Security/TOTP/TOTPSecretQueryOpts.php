<?php
namespace Minds\Core\Security\TOTP;

use Minds\Common\Repository\AbstractRepositoryOpts;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setDeviceId(string $deviceId)
 * @method int getDeviceId()
 */
class TOTPSecretQueryOpts extends AbstractRepositoryOpts
{
    /** @var string */
    protected $userGuid;

    /** @var string */
    protected $deviceId = 'app';
}
