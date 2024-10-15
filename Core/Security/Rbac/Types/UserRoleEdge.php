<?php
namespace Minds\Core\Security\Rbac\Types;

use Minds\Core\Feeds\GraphQL\Types\UserNode;
use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\Security\Rbac\Models\Role;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class UserRoleEdge implements EdgeInterface
{
    public function __construct(
        private User $user,
        /** @var Role[] */
        private array $roles,
        private string $cursor = ''
    ) {
    }

    public function getCursor(): string
    {
        return $this->cursor;
    }

    #[Field]
    public function getNode(): UserNode
    {
        return new UserNode($this->user);
    }

    /**
     * @return Role[]
     */
    #[Field]
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Gets user.
     * @return User - the user.
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
