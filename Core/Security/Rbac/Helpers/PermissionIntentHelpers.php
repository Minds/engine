<?php
declare(strict_types=1);

namespace Minds\Core\Security\Rbac\Helpers;

use Minds\Core\Security\Rbac\Enums\PermissionIntentTypeEnum;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Models\PermissionIntent;

/**
 * Helper class for permission intents.
 */
class PermissionIntentHelpers
{
    /** Ordered array of permissions to be controlled by intents. */
    public const CONTROLLABLE_PERMISSION_IDS = [
        PermissionsEnum::CAN_CREATE_POST,
        PermissionsEnum::CAN_INTERACT,
        PermissionsEnum::CAN_UPLOAD_VIDEO,
        PermissionsEnum::CAN_CREATE_CHAT_ROOM,
        PermissionsEnum::CAN_COMMENT
    ];

    /**
     * Get the default intent type for a given permission on tenant networks.
     * @param PermissionsEnum $permissionId - the permission ID.
     * @return PermissionIntentTypeEnum - the default intent type for the given permission.
     */
    public function getTenantDefaultIntentType(PermissionsEnum $permissionId): PermissionIntentTypeEnum
    {
        return match ($permissionId) {
            PermissionsEnum::CAN_CREATE_POST => PermissionIntentTypeEnum::WARNING_MESSAGE,
            PermissionsEnum::CAN_INTERACT => PermissionIntentTypeEnum::WARNING_MESSAGE,
            PermissionsEnum::CAN_UPLOAD_VIDEO => PermissionIntentTypeEnum::WARNING_MESSAGE,
            PermissionsEnum::CAN_CREATE_CHAT_ROOM => PermissionIntentTypeEnum::WARNING_MESSAGE,
            PermissionsEnum::CAN_COMMENT => PermissionIntentTypeEnum::WARNING_MESSAGE,
            default => PermissionIntentTypeEnum::WARNING_MESSAGE,
        };
    }

    /**
     * Get the default (fixed) intents for non-tenant networks.
     * @return array - the default intents for non-tenant networks.
     */
    public function getNonTenantDefaults(): array
    {
        return [
            new PermissionIntent(
                permissionId: PermissionsEnum::CAN_CREATE_POST,
                intentType: PermissionIntentTypeEnum::HIDE,
                membershipGuid: null
            ),
            new PermissionIntent(
                permissionId: PermissionsEnum::CAN_INTERACT,
                intentType: PermissionIntentTypeEnum::HIDE,
                membershipGuid: null
            ),
            new PermissionIntent(
                permissionId: PermissionsEnum::CAN_UPLOAD_VIDEO,
                intentType: PermissionIntentTypeEnum::HIDE,
                membershipGuid: null
            ),
            new PermissionIntent(
                permissionId: PermissionsEnum::CAN_CREATE_CHAT_ROOM,
                intentType: PermissionIntentTypeEnum::HIDE,
                membershipGuid: null
            ),
            new PermissionIntent(
                permissionId: PermissionsEnum::CAN_COMMENT,
                intentType: PermissionIntentTypeEnum::HIDE,
                membershipGuid: null
            )
        ];
    }
}
