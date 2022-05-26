<?php

namespace Minds\Core\Notifications\Push\System\Targets;

use Minds\Exceptions\UserErrorException;

/**
 *
 */
class SystemPushNotificationTargetsList
{
    public const TARGETS_LIST = [
        'all-devices' => AllDevices::class,
        'all-android-devices' => AllAndroidAppDevices::class
    ];

    public static function getTargetHandlerFromShortName(string $targetShortName): SystemPushNotificationTargetInterface
    {
        $target = self::TARGETS_LIST[$targetShortName];
        return new $target();
    }

    /**
     * Get target handler by class name.
     * @param string $className - class name of target list.
     * @return SystemPushNotificationTargetInterface - target list.
     */
    public static function getTargetHandlerFromClassName(string $className): SystemPushNotificationTargetInterface
    {
        $className = ucfirst($className);
        $fullClassPath = "Minds\\Core\\Notifications\\Push\\System\\Targets\\$className";
        if (class_exists($fullClassPath)) {
            $class = new $fullClassPath();
            if ($class instanceof SystemPushNotificationTargetInterface) {
                return $class;
            }
        }
        throw new UserErrorException("Target list not found"); // SEE?
    }
}
