<?php

namespace Minds\Core\MultiTenant\Enums;

use Error;

enum TenantPlanEnum
{
    case TEAM;
    case COMMUNITY;
    case ENTERPRISE;

    public static function fromString(string $str): TenantPlanEnum
    {
        $str = \strtoupper($str);
        try {
            return constant(self::class . "::$str");
        } catch (Error) {
            return null;
        }
    }
}
