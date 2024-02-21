<?php
namespace Minds\Core\Notifications\Push\ManualSend\Enums;

/**
 * Enum for different platforms that can receive push notifications.
 */
enum PushNotificationPlatformEnum: string
{
    case ANDROID = 'android';
    case IOS = 'ios';
}
