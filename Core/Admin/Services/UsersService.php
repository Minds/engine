<?php
namespace Minds\Core\Admin\Services;

use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\Security\Rbac\Types\UserRoleEdge;

class UsersService
{
    public function __construct(
        private readonly RolesService $rolesService,
    ) {
        
    }

    /**
     * Returns a list of users and their emails
     */
    public function listUsers(int $limit, int $offset): array
    {
        $users = array_map(function (UserRoleEdge $userEdge) {
            $user = $userEdge->getNode()->getUser();
            return [...$user->export(), 'email' => $user->getEmail()];
        }, iterator_to_array($this->rolesService->getUsersByRole(
            limit: $limit,
            loadAfter: base64_encode($offset)
        )));

        return $users;
    }
}
