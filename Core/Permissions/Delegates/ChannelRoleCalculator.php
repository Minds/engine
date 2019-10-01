<?php

namespace Minds\Core\Permissions\Delegates;

use Minds\Traits\MagicAttributes;
use Minds\Core\Permissions\Roles\Roles;
use Minds\Core\Permissions\Roles\Role;
use Minds\Entities\User;
use Minds\Common\ChannelMode;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Factory as EntitiesFactory;

class ChannelRoleCalculator extends BaseRoleCalculator
{
    use MagicAttributes;

    private $channels = [];
    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function __construct(User $user = null, Roles $roles, EntitiesBuilder $entitiesBuilder = null)
    {
        parent::__construct($user, $roles);
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * Retrieves permissions for an entity relative to the user's role in a channel
     * Retrieves the role from the in memory cache if we've seen this channel before during this request
     * Else checks the user's membership against the channel.
     *
     * @param $entity an entity from a channel
     *
     * @return Role
     */
    public function calculate($entity): Role
    {
        if (isset($this->channels[$entity->getOwnerGuid()])) {
            return $this->channels[$entity->getOwnerGuid()];
        }
        $role = null;
        $channel = $this->getChannelForEntity($entity);
        if ($this->user === null) {
            $role = $this->getChannelNonSubscriberRole($channel);
        } elseif ($entity->getOwnerGuid() === $this->user->getGuid()) {
            $role = $this->roles->getRole(Roles::ROLE_CHANNEL_OWNER);
        } elseif ($this->user->isSubscribed($channel->getGuid())) {
            $role = $this->getChannelSubscriberRole($channel);
        } else {
            $role = $this->getChannelNonSubscriberRole($channel);
        }
        $this->channels[$channel->getGuid()] = $role;

        return $role;
    }

    /**
     * Gets the channel user object for a given entity
     * Return the denormalized version
     * Or look it up in cassandra / entities cache
     * @param entity
     * @return user
     */
    protected function getChannelForEntity($entity) : User
    {
        if ($entity->getType() === 'user') {
            return $entity;
        } elseif (method_exists($entity, 'getOwnerObj')) {
            return $this->entitiesBuilder->build($entity->getOwnerObj());
        } else {
            return $this->entitiesBuilder->single($entity->getOwnerGuid());
        }
    }


    /**
     * Gets a subscriber's role based on channel mode
     * @param User
     * @return Role
     */
    protected function getChannelSubscriberRole(User $channel) : Role
    {
        switch ($channel->getMode()) {
            case ChannelMode::CLOSED:
                return $this->roles->getRole(Roles::ROLE_CLOSED_CHANNEL_SUBSCRIBER);
            case ChannelMode::MODERATED:
                return $this->roles->getRole(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
            case ChannelMode::OPEN:
                return $this->roles->getRole(Roles::ROLE_OPEN_CHANNEL_SUBSCRIBER);
        }
    }

    /**
     * Gets a non-subscriber's role based on channel mode
     * @param User
     * @return Role
     */
    protected function getChannelNonSubscriberRole(User $channel) : Role
    {
        switch ($channel->getMode()) {
            case ChannelMode::CLOSED:
                if ($this->user === null) {
                    return $this->roles->getRole(Roles::ROLE_LOGGED_OUT_CLOSED);
                }
                return $this->roles->getRole(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
            case ChannelMode::MODERATED:
                if ($this->user === null) {
                    return $this->roles->getRole(Roles::ROLE_LOGGED_OUT);
                }
                return $this->roles->getRole(Roles::ROLE_MODERATED_CHANNEL_NON_SUBSCRIBER);
            case ChannelMode::OPEN:
                if ($this->user === null) {
                    return $this->roles->getRole(Roles::ROLE_LOGGED_OUT);
                }
                return $this->roles->getRole(Roles::ROLE_OPEN_CHANNEL_NON_SUBSCRIBER);
        }
    }
}
