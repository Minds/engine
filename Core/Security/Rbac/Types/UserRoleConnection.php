<?php
namespace Minds\Core\Security\Rbac\Types;

use Minds\Core\GraphQL\Types\Connection;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;

#[Type]
class UserRoleConnection extends Connection
{
    /**
     * @return UserRoleEdge[]
     */
    #[Field]
    public function getEdges(): array
    {
        return $this->edges;
    }
}
