<?php
declare(strict_types=1);

namespace Minds\Core\Verification\Models;

use Minds\Core\Verification\Exceptions\VerificationRequestDeviceTypeNotFoundException;

class VerificationRequestDeviceType
{
    public const ANDROID = 1;
    public const IOS = 2;
    public const PUSH_NOTIFICATION_SERVICE_MAPPING = [
        self::ANDROID => 'fcm',
        self::IOS => 'apns'
    ];

    /**
     * @param string $deviceTypeId
     * @return int
     * @throws VerificationRequestDeviceTypeNotFoundException
     */
    public static function fromId(string $deviceTypeId): int
    {
        return match ($deviceTypeId) {
            'android' => self::ANDROID,
            'ios' => self::IOS,
            default => throw new VerificationRequestDeviceTypeNotFoundException()
        };
    }
}
