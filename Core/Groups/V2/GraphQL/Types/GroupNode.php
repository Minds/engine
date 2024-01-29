<?php

namespace Minds\Core\Groups\V2\GraphQL\Types;

use Minds\Core\Di\Di;
use Minds\Core\Feeds\GraphQL\Types\AbstractEntityNode;
use Minds\Core\Groups\Membership as GroupMembership;
use Minds\Entities\Group;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * The GroupNode returns relevant information about the Group.
 */
#[Type]
class GroupNode extends AbstractEntityNode
{
    public function __construct(
        protected Group $group,
    ) {
        $this->entity = $group;
    }

    #[Field]
    public function getName(): string
    {
        return $this->entity->getName();
    }

    #[Field]
    public function getMembersCount(): int
    {
        return Di::_()->get(GroupMembership::class)->getMembersCountByGuid($this->entity->getGuid()) ?? 0;
    }
}
