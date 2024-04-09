<?php
declare(strict_types=1);

namespace Minds\Core\GraphQL\Services;

use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\Session;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface;

class AuthorizationService implements AuthorizationServiceInterface
{
    public function __construct(
        private readonly ?RolesService $rolesService = null
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isAllowed(string $right, mixed $subject = null): bool
    {
        // If no subject is provided we will check against logged-in user.
        if (!$subject) {
            $subject = Session::getLoggedInUser();
        }

        if (!$subject instanceof User) {
            return false;
        }

        return match ($right) {
            'ROLE_ADMIN' => $subject->isAdmin(),
            'PERMISSION_RSS_SYNC' => $this->rolesService->hasPermission(
                $subject,
                PermissionsEnum::CAN_USE_RSS_SYNC
            ),
            'PERMISSION_CAN_MODERATE_CONTENT' => $this->rolesService->hasPermission(
                $subject,
                PermissionsEnum::CAN_MODERATE_CONTENT
            ),
            default => false,
        };
    }
}
