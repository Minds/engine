<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use Minds\Core\MultiTenant\Enums\TenantUserRoleEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class TenantUser
{
    public function __construct(
        #[Field(outputType: 'String!')] public readonly int $guid,
        #[Field] public readonly string                     $username,
        #[Field] public readonly int                        $tenantId,
        #[Field] public TenantUserRoleEnum                  $role = TenantUserRoleEnum::USER,
        public readonly string                              $plainPassword = '',
    ) {
    }

    /**
     * Return a cloned TenantUser with given user guid.
     * @param int $guid - user guid.
     * @return TenantUser - cloned TenantUser with given user guid.
     */
    public function withGuid(int $guid): TenantUser
    {
        return new TenantUser(
            guid: $guid,
            username: $this->username,
            tenantId: $this->tenantId,
            role: $this->role,
            plainPassword: $this->plainPassword,
        );
    }
}
