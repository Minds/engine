<?php
namespace Minds\Core\Security\Rbac\Models;

use Minds\Core\Security\Rbac\Enums\PermissionIntentTypeEnum;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Model for a permission intent.
 */
#[Type]
class PermissionIntent
{
    public function __construct(
        #[Field] public readonly PermissionsEnum $permissionId,
        #[Field] public ?PermissionIntentTypeEnum $intentType,
        #[Field(outputType: 'String')] public ?int $membershipGuid
    ) {
    }

    /**
     * Export the permission intent to an array.
     * @return array - the exported permission intent.
     */
    public function export(): array
    {
        return [
            'permission_id' => $this->permissionId->name,
            'intent_type' => $this->intentType->name,
            'membership_guid' => $this->membershipGuid
        ];
    }
}
