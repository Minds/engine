<?php

namespace Minds\Core\Permissions;

use Minds\Traits\MagicAttributes;
use Minds\Entities\User;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Permissions\Roles\Roles;
use Minds\Core\Permissions\Roles\Role;
use Minds\Core\Permissions\Delegates\ChannelRoleCalculator;
use Minds\Core\Permissions\Delegates\GroupRoleCalculator;
use Minds\Common\Access;
use Minds\Core\Di\Di;
use Minds\Exceptions\ImmutableException;

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
    private $entities;
    /** @var array */
    private $groups;
    /** @var array */
    private $channels;
    /** @var ChannelRoleCalculator */
    private $channelRoleCalculator;
    /** @var GroupRoleCalculator */
    private $groupRoleCalculator;
    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function __construct(User $user, Roles $roles = null, EntitiesBuilder $entitiesBuilder = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->roles = $roles ?: new Roles();
        $this->user = $user;
        $this->isAdmin = $user->isAdmin();
        $this->isBanned = $user->isBanned();
        $this->groups = [];
        $this->channels = [];
        $this->entities = [];
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->channels[$user->getGUID()] = $user;
        $this->channelRoleCalculator = new ChannelRoleCalculator($this->user, $this->roles);
        $this->groupRoleCalculator = new GroupRoleCalculator($this->user, $this->roles, $entitiesBuilder);
    }

    /**
     * Permissions are user aware. This bomb function is to keep the user from being changed after instantiation.
     *
     * @throws ImmutableException
     */
    public function setUser(User $user): void
    {
        throw new ImmutableException('User can only be set in the constructor');
    }

    /**
     * Takes an array of entities and checks their permissions
     * Builds up collections of permissions based on the user's relationships to the entity
     * Any found channels and their roles are accessible in the channelRoleCalculator
     * Any found groups and their roles are in the groupRoleCalculator
     * All requested entities and the user's role is available in $this->entities.
     *
     * @param array entities an array of entities for calculating permissions
     */
    public function calculate(array $entities = []): void
    {
        foreach ($entities as $entity) {
            $this->entities[$entity->getGUID()] = $this->getRoleForEntity($entity);
        }
    }

    private function getRoleForEntity($entity): Role
    {
        $role = null;
        //Access id is the best way to determine what the parent entity is
        //Any of the access flags are a channel
        //Anything else is a group guid
        switch ($entity->getAccessId()) {
            case Access::UNLISTED:
            case Access::LOGGED_IN:
            case Access::PUBLIC:
            case Access::UNKNOWN:
                $role = $this->channelRoleCalculator->calculate($entity);
                break;
            default:
                $role = $this->groupRoleCalculator->calculate($entity);
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

    /**
     * Export the nested objects.
     *
     * @return array serialized objects
     */
    public function export(): array
    {
        $export = [];
        $export['user'] = $this->user->export();
        $export['channels'] = $this->getChannels();
        $export['groups'] = $this->getGroups();
        $export['entities'] = $this->entities;

        return $export;
    }

    /**
     * @return array channel guids with the user's role
     */
    public function getChannels(): array
    {
        return $this->channelRoleCalculator->getChannels();
    }

    /**
     * @return array group guids with the user's role
     */
    public function getGroups(): array
    {
        return $this->groupRoleCalculator->getGroups();
    }

    /**
     * @return array serialized objects
     */
    public function jsonSerialize(): array
    {
        return $this->export();
    }
}
