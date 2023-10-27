<?php

namespace Minds\Core\MultiTenant\Enums;

use Exception;

enum TenantUserRoleEnum: int
{
    case OWNER = 1;
    case ADMIN = 2;
    case USER = 3;

    /**
     * @param string $roleLabel
     * @return self
     * @throws Exception
     */
    public static function fromRoleLabel(string $roleLabel): self
    {
        return match ($roleLabel) {
            'ROLE_TENANT_OWNER' => self::OWNER,
            'ROLE_TENANT_ADMIN' => self::ADMIN,
            'ROLE_TENANT_USER' => self::USER,
            default => throw new Exception('Invalid role'),
        };
    }
}
