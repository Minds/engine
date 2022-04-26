<?php
namespace Minds\Core\Notifications\Push\DeviceSubscriptions;

use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setToken(string $token)
 * @method string getToken()
 * @method string getService()
 */
class DeviceSubscription
{
    use MagicAttributes;

    /** @var string */
    const SERVICE_APNS = 'apns';

    /** @var string */
    const SERVICE_FCM = 'fcm';

    /** @var string */
    const SERVICE_WEBPUSH = 'webpush';

    /** @var string */
    protected $userGuid;

    /** @var string */
    protected $token;

    /** @var string */
    protected $service;

    /**
     * @param string $service
     * @return self
     */
    public function setService(string $service): self
    {
        if (!in_array($service, [ self::SERVICE_APNS, self::SERVICE_FCM, self::SERVICE_WEBPUSH ], true)) {
            throw new \Exception('Invalid service');
        }
        $this->service = $service;

        return $this;
    }
}
