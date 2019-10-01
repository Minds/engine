<?php

namespace Minds\Core\Permissions\Roles;

use Zend\Permissions\Rbac\Rbac;
use ReflectionClass;

class Roles extends Rbac
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_BANNED = 'banned';
    public const ROLE_CHANNEL_ADMIN = 'channel_admin';
    public const ROLE_CHANNEL_MODERATOR = 'channel_moderator';
    public const ROLE_CHANNEL_OWNER = 'channel_owner';
    public const ROLE_CLOSED_CHANNEL_SUBSCRIBER = 'closed_channel_subscriber';
    public const ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER = 'closed_channel_nonsubscriber';
    public const ROLE_CLOSED_GROUP_SUBSCRIBER = 'closed_group_subscriber';
    public const ROLE_CLOSED_GROUP_NON_SUBSCRIBER = 'closed_group_nonsubscriber';
    public const ROLE_ENTITY_OWNER = 'entity_owner';
    public const ROLE_GROUP_ADMIN = 'group_admin';
    public const ROLE_GROUP_MODERATOR = 'group_moderator';
    public const ROLE_GROUP_OWNER = 'group_owner';
    public const ROLE_LOGGED_OUT = 'logged_out';
    public const ROLE_LOGGED_OUT_CLOSED = 'logged_out_closed';
    public const ROLE_MODERATED_CHANNEL_SUBSCRIBER = 'moderated_channel_subscriber';
    public const ROLE_MODERATED_CHANNEL_NON_SUBSCRIBER = 'moderated_channel_nonsubscriber';
    public const ROLE_OPEN_CHANNEL_SUBSCRIBER = 'open_channel_subscriber';
    public const ROLE_OPEN_CHANNEL_NON_SUBSCRIBER = 'open_channel_nonsubscriber';
    public const ROLE_OPEN_GROUP_SUBSCRIBER = 'open_group_subscriber';
    public const ROLE_OPEN_GROUP_NON_SUBSCRIBER = 'open_group_nonsubscriber';

    public function __construct()
    {
        $this->addRole(new AdminRole());
        $this->addRole(new BannedRole());
        $this->addRole(new ChannelAdminRole());
        $this->addRole(new ChannelModeratorRole());
        $this->addRole(new ChannelOwnerRole());
        $this->addRole(new ClosedChannelNonSubscriberRole());
        $this->addRole(new ClosedChannelSubscriberRole());
        $this->addRole(new ClosedGroupNonSubscriberRole());
        $this->addRole(new ClosedGroupSubscriberRole());
        $this->addRole(new EntityOwnerRole());
        $this->addRole(new GroupAdminRole());
        $this->addRole(new GroupModeratorRole());
        $this->addRole(new GroupOwnerRole());
        $this->addRole(new LoggedOutRole());
        $this->addRole(new LoggedOutClosedRole());
        $this->addRole(new ModeratedChannelNonSubscriberRole());
        $this->addRole(new ModeratedChannelSubscriberRole());
        $this->addRole(new OpenChannelNonSubscriberRole());
        $this->addRole(new OpenChannelSubscriberRole());
        $this->addRole(new OpenGroupNonSubscriberRole());
        $this->addRole(new OpenGroupSubscriberRole());
    }

    final public static function toArray() : array
    {
        return (new ReflectionClass('Minds\Core\Permissions\Roles\Roles'))->getConstants();
    }
}
