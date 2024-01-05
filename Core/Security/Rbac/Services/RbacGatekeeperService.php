<?php
namespace Minds\Core\Security\Rbac\Services;

use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Exceptions\RbacNotAllowed;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;

class RbacGatekeeperService
{
    public function __construct(
        private RolesService $rolesService,
        private ActiveSession $activeSession,
    ) {
        
    }

    /**
     * Returns if the users is allowed to use the requested permission.
     * A logged out user will not have access. We may want to add in a 'logged-out' role
     * in the future
     * @throws RbacNotAllowed
     */
    public function isAllowed(
        PermissionsEnum $permission,
        User $user = null,
        bool $throwException = true
    ): bool {
        $user ??= $this->activeSession->getUser();

        if (!$user) {
            return false;
        }

        $hasPermission = $this->rolesService->hasPermission($user, $permission);

        if ($throwException && !$hasPermission) {
            throw new RbacNotAllowed($permission);
        }

        return $hasPermission;
    }
}
