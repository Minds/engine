<?php

namespace Minds\Core\Permissions\Delegates;

use Minds\Traits\MagicAttributes;
use Minds\Core\Di\Di;
use Minds\Core\Permissions\Roles\Role;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Core\Permissions\Roles\Roles;

class GroupRoleCalculator extends BaseRoleCalculator
{
    use MagicAttributes;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;
    /** @var array */
    private $groups = [];

    public function __construct(User $user, Roles $roles, EntitiesBuilder $entitiesBuilder = null)
    {
        parent::__construct($user, $roles);
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * Retrieves permissions for an entity relative to the user's role in a group
     * Retrieves the role from the in memory cache if we've seen this group before during this request
     * Else gets the group and checks the user's membership.
     *
     * @param $entity an entity belonging to a group
     *
     * @return Role
     */
    public function calculate($entity): Role
    {
        if (isset($this->groups[$entity->getAccessId()])) {
            return $this->groups[$entity->getAccessId()];
        }
        $group = $this->entitiesBuilder->single($entity->getAccessId());
        $role = null;
        if ($group->isCreator($this->user)) {
            $role = $this->roles->getRole(Roles::ROLE_GROUP_OWNER);
        } elseif ($group->isOwner($this->user)) {
            $role = $this->roles->getRole(Roles::ROLE_GROUP_ADMIN);
        } elseif ($group->isBanned($this->user)) {
            $role = $this->roles->getRole(Roles::ROLE_BANNED);
        } elseif ($group->isModerator($this->user)) {
            $role = $this->roles->getRole(Roles::ROLE_GROUP_MODERATOR);
        } elseif ($group->isMember($this->user)) {
            $role = $this->roles->getRole(Roles::ROLE_GROUP_SUBSCRIBER);
        } else {
            $role = $this->roles->getRole(Roles::ROLE_GROUP_NON_SUBSCRIBER);
        }
        $this->groups[$entity->getAccessId()] = $role;

        return $role;
    }
}
