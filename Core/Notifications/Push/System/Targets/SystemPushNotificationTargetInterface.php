<?php

namespace Minds\Core\Notifications\Push\System\Targets;

use Generator;

/**
 *
 */
interface SystemPushNotificationTargetInterface
{
    public function getList(): Generator;
}
