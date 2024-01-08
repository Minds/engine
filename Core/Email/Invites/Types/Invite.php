<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Types;

use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\GraphQL\Types\NodeInterface;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Core\Security\Rbac\Models\Role;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

#[Type]
class Invite implements NodeInterface
{
    /**
     * @param int $inviteId
     * @param string $email
     * @param InviteEmailStatusEnum $status
     * @param string $bespokeMessage
     * @param int $createdTimestamp
     * @param int|null $sendTimestamp
     * @param int[]|null $roles
     * @param int[]|null $groups
     */
    public function __construct(
        #[Field] public readonly int                   $inviteId,
        public readonly int                            $tenantId,
        public readonly int                            $ownerGuid,
        #[Field] public readonly string                $email,
        public readonly string                         $inviteToken,
        #[Field] public readonly InviteEmailStatusEnum $status,
        #[Field] public readonly string                $bespokeMessage,
        #[Field] public readonly int                   $createdTimestamp,
        #[Field] public readonly ?int                  $sendTimestamp = null,
        private readonly ?array                        $roles = null,
        private readonly ?array                        $groups = null,
    ) {
    }

    #[Field]
    public function getId(): ID
    {
        return new ID("invite-" . $this->inviteId);
    }

    /**
     * @return Role[]|null
     */
    #[Field]
    public function getRoles(): ?array
    {
        $roles = [];
        foreach ($this->roles as $roleId) {
            $roleEnum = RolesEnum::from($roleId);
            $roles[$roleEnum->value] = new Role(
                id: $roleEnum->value,
                name: $roleEnum->name,
                permissions: []
            );
        }
        return $roles;
    }

    /**
     * @return int[]|null
     */
    #[Field]
    public function getGroups(): ?array
    {
        return $this->groups;
    }
}
