<?php

namespace Minds\Core\Verification\Models;

use Minds\Core\Verification\Exceptions\VerificationRequestDeviceTypeNotFoundException;

class VerificationRequestDeviceType
{
    public const ANDROID = 1;
    public const IOS = 2;

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
