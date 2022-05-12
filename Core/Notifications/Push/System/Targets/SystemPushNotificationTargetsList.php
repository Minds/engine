<?php

namespace Minds\Core\Notifications\Push\System\Targets;

/**
 *
 */
class SystemPushNotificationTargetsList
{
    public const TARGETS_LIST = [
        'all-devices' => AllDevices::class
    ];

    public static function getTargetHandlerFromShortName(string $targetShortName): SystemPushNotificationTargetInterface
    {
        $target = self::TARGETS_LIST[$targetShortName];
        return new $target();
    }
}
