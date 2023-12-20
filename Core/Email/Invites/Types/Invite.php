<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Types;

use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\GraphQL\Types\NodeInterface;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
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
        #[Field] public readonly string                $email,
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
     * @return int[]|null
     */
    #[Field]
    public function getRoles(): ?array
    {
        return $this->roles;
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
