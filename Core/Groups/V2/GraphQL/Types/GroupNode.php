<?php

namespace Minds\Core\Groups\V2\GraphQL\Types;

use Minds\Core\Feeds\GraphQL\Types\AbstractEntityNode;
use Minds\Entities\Group;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * The GroupNode returns relevant information about the Group.
 */
#[Type]
class GroupNode extends AbstractEntityNode
{
    public function __construct(
        protected Group $group,
    )
    {
        $this->entity = $group;
    }

    public function getEntity(): Group
    {
        return $this->entity;
    }
}
