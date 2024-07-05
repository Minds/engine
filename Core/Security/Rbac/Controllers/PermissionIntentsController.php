<?php
declare(strict_types=1);

namespace Minds\Core\Security\Rbac\Controllers;

use Minds\Core\Security\Rbac\Enums\PermissionIntentTypeEnum;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Models\PermissionIntent;
use Minds\Core\Security\Rbac\Services\PermissionIntentsService;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;

/**
 * Controller for permission intents.
 */
class PermissionIntentsController
{
    public function __construct(
        private readonly PermissionIntentsService $service
    ) {
    }

    /**
     * Get permission intents.
     * @return PermissionIntent[] array of permission intents.
     */
    #[Query]
    #[Logged]
    public function getPermissionIntents(): array
    {
        return $this->service->getPermissionIntents();
    }

    /**
     * Set a permission intent.
     * @param PermissionsEnum $permissionId - the permission ID.
     * @param PermissionIntentTypeEnum $intentType - the type of the permission intent.
     * @param string|null $membershipGuid - any membership guid bound to the intent.
     * @return PermissionIntent
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function setPermissionIntent(
        PermissionsEnum $permissionId,
        PermissionIntentTypeEnum $intentType,
        ?string $membershipGuid = null,
        #[InjectUser] ?User $loggedInUser = null,
    ): ?PermissionIntent {
        $success = $this->service->setPermissionIntent(
            permissionId: $permissionId,
            intentType: $intentType,
            membershipGuid: $membershipGuid
        );

        return $success ? new PermissionIntent(
            permissionId: $permissionId,
            intentType: $intentType,
            membershipGuid: $membershipGuid ? (int) $membershipGuid : null
        ) : null;
    }
}
