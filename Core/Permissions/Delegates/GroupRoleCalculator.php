<?php

namespace Minds\Core\Permissions\Delegates;

use Minds\Traits\MagicAttributes;
use Minds\Core\Di\Di;
use Minds\Core\Permissions\Roles\Role;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Core\Permissions\Roles\Roles;
use Minds\Entities\Group;

class GroupRoleCalculator extends BaseRoleCalculator
{
    use MagicAttributes;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;
    /** @var array */
    private $groups = [];


    public function __construct(User $user = null, Roles $roles, EntitiesBuilder $entitiesBuilder = null)
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
        if ($entity->getType() === 'group') {
            $group = $entity;
        } else {
            $group = $this->entitiesBuilder->single($entity->getAccessId());
        }

        $role = null;
        if ($this->user === null) {
            $role = $this->getGroupNonSubscriberRole($group);
        } elseif ($group->isCreator($this->user)) {
            $role = $this->roles->getRole(Roles::ROLE_GROUP_OWNER);
        } elseif ($group->isOwner($this->user)) {
            $role = $this->roles->getRole(Roles::ROLE_GROUP_ADMIN);
        } elseif ($group->isBanned($this->user)) {
            $role = $this->roles->getRole(Roles::ROLE_BANNED);
        } elseif ($group->isModerator($this->user)) {
            $role = $this->roles->getRole(Roles::ROLE_GROUP_MODERATOR);
        } elseif ($group->isMember($this->user)) {
            $role = $this->getGroupSubscriberRole($group);
        } else {
            $role = $this->getGroupNonSubscriberRole($group);
        }
        
        $this->groups[$group->getGuid()] = $role;

        return $role;
    }


    /**
    * Gets a subscriber's role based on group mode
    * @param Group
    * @return Role
    */
    protected function getGroupSubscriberRole(Group $group) : Role
    {
        if ($group->isPublic()) {
            return $this->roles->getRole(Roles::ROLE_OPEN_GROUP_SUBSCRIBER);
        } else {
            return $this->roles->getRole(Roles::ROLE_CLOSED_GROUP_SUBSCRIBER);
        }
    }

    /**
     * Gets a non-subscriber's role based on channel mode
     * @param Group
     * @return Role
     */
    protected function getGroupNonSubscriberRole(Group $group) : Role
    {
        if ($group->isPublic()) {
            if ($this->user === null) {
                $this->roles->getRole(Roles::ROLE_LOGGED_OUT);
            }
            return $this->roles->getRole(Roles::ROLE_OPEN_GROUP_NON_SUBSCRIBER);
        } else {
            if ($this->user === null) {
                $this->roles->getRole(Roles::ROLE_LOGGED_OUT_CLOSED);
            }
            return $this->roles->getRole(Roles::ROLE_CLOSED_GROUP_NON_SUBSCRIBER);
        }
    }
}
