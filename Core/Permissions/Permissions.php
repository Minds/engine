<?php

namespace Minds\Core\Permissions;

use Minds\Core\Di\Di;
use Minds\Traits\MagicAttributes;
use Minds\Entities\User;
use Minds\Entities\Group;
use Minds\Core\Permissions\Roles\Roles;
use Minds\Core\EntitiesBuilder;

class Permissions implements \JsonSerializable
{
    use MagicAttributes;

    /** @var bool */
    private $isAdmin = false;
    /** @var bool */
    private $isBanned = false;
    /** @var User */
    private $user;
    /** @var Roles */
    private $roles;
    /** @var array */
    private $channels;
    /** @var array */
    private $groups;
    /** @var array */
    private $entities;
    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function __construct(User $user, EntitiesBuilder $entitiesBuilder = null, Roles $roles = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->roles = $roles ?: new Roles();
        $this->user = $user;
        $this->isAdmin = $user->isAdmin();
        $this->isBanned = $user->isBanned();
        $this->groups = [];
        $this->channels = [];
        $this->entities = [];
        $this->channels[$user->guid] = $user;
    }

    /**
     * @param array entities an array of entities for calculating permissions
     */
    public function calculate(array $entities = [])
    {
        foreach ($entities as $entity) {
            $role = $this->getRoleForEntity($entity);
            $this->entities[$entity->guid] = $role;
        }
    }

    private function getRoleForEntity($entity)
    {
        $role = null;
        error_log("get role for entity");
        error_log(var_export($entity->getContainerEntity() instanceof User, true));
        if ($entity->getContainerEntity() instanceof User) {
            $role = $this->getChannelRole($entity->getContainerEntity());
        }
        if ($entity->getContainerEntity() instanceof Group) {
            $role = $this->getGroupRole($entity->getContainerEntity());
        }
        //Apply global overrides
        if ($this->isAdmin) {
            $role = $this->roles->getRole(Roles::ROLE_ADMIN);
        }
        if ($this->isBanned) {
            $role = $this->roles->getRole(Roles::ROLE_BANNED);
        }

        return $role;
    }

    private function getChannelRole(User $channel)
    {
        error_log("Getting channel role");
        $this->channels[$channel->guid] = $channel;
        if($channel->guid === $this->user->guid) {
            return $this->roles->getRole(Roles::ROLE_CHANNEL_OWNER);
        }
        if ($this->user->isSubscribed($owner->guid)) {
            return $this->roles->getRole(Roles::ROLE_CHANNEL_SUBSCRIBER);
        } else {
            return $this->roles->getRole(Roles::ROLE_CHANNEL_NON_SUBSCRIBER);
        }
    }

    private function getGroupOwner(Group $group)
    {
        $this->groups[$group->guid] = $group;
    }

    public function export()
    {
        $export = [];
        $export['user'] = $this->user->export();
        $export['entities'] = $this->entities;

        return $export;
    }

    public function jsonSerialize()
    {
        return $this->export();
    }
}
