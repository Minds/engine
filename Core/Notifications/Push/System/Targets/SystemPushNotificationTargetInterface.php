<?php

namespace Minds\Core\Notifications\Push\System\Targets;

use Generator;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;

/**
 *
 */
interface SystemPushNotificationTargetInterface
{
    /**
     * @return Generator<DeviceSubscription>
     */
    public function getList(): Generator;
}
