<?php
declare(strict_types=1);

namespace Minds\Core\GraphQL\Services;

use Exception;
use Minds\Core\MultiTenant\Enums\TenantUserRoleEnum;
use Minds\Core\MultiTenant\Types\TenantUser;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface;

class AuthorizationService implements AuthorizationServiceInterface
{
    /**
     * @inheritDoc
     * @throws Exception
     */
    public function isAllowed(string $right, mixed $subject = null): bool
    {
        return match ($right) {
            'ROLE_ADMIN' => $subject instanceof User && $subject->isAdmin(),
            'ROLE_TENANT_OWNER',
            'ROLE_TENANT_ADMIN',
            'ROLE_TENANT_USER' => $this->tenantUserACL(TenantUserRoleEnum::fromRoleLabel($right), $subject),
            default => false,
        };
    }

    private function tenantUserACL(TenantUserRoleEnum $requiredRole, TenantUser $user): bool
    {
        return $user->role === $requiredRole;
    }
}
