<?php

namespace Minds\Core\Permissions\Roles;

use Zend\Permissions\Rbac\Rbac;

class Roles extends Rbac
{
    public const ROLE_LOGGED_OUT = 'logged_out';
    public const ROLE_BANNED = 'banned';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_CHANNEL_ADMIN = 'channel_admin';
    public const ROLE_CHANNEL_MODERATOR = 'channel_moderator';
    public const ROLE_CHANNEL_OWNER = 'channel_owner';
    public const ROLE_CHANNEL_SUBSCRIBER = 'channel_subscriber';
    public const ROLE_CHANNEL_NON_SUBSCRIBER = 'channel_nonsubscriber';
    public const ROLE_GROUP_ADMIN = 'group_admin';
    public const ROLE_GROUP_MODERATOR = 'group_moderator';
    public const ROLE_GROUP_OWNER = 'group_owner';
    public const ROLE_GROUP_SUBSCRIBER = 'group_subscriber';
    public const ROLE_GROUP_NON_SUBSCRIBER = 'group_nonsubscriber';

    public const FLAG_APPOINT_ADMIN = 'appoint_admin';
    public const FLAG_APPOINT_MODERATOR = 'appoint_moderator';
    public const FLAG_APPROVE_SUBSCRIBER = 'approve_subscriber';
    public const FLAG_CREATE_CHANNEL = 'create_channel';
    public const FLAG_CREATE_COMMENT = 'create_comment';
    public const FLAG_CREATE_GROUP = 'create_group';
    public const FLAG_CREATE_POST = 'create_post';
    public const FLAG_DELETE_CHANNEL = 'delete_channel';
    public const FLAG_DELETE_COMMENT = 'delete_comment';
    public const FLAG_DELETE_GROUP = 'delete_group';
    public const FLAG_DELETE_POST = 'delete_post';
    public const FLAG_EDIT_CHANNEL = 'edit_channel';
    public const FLAG_EDIT_COMMENT = 'edit_comment';
    public const FLAG_EDIT_GROUP = 'edit_group';
    public const FLAG_EDIT_POST = 'edit_post';
    public const FLAG_INVITE = 'invite';
    public const FLAG_JOIN = 'join';
    public const FLAG_JOIN_GATHERING = 'gathering';
    public const FLAG_MESSAGE = 'message';
    public const FLAG_SUBSCRIBE = 'subscribe';
    public const FLAG_TAG = 'tag';
    public const FLAG_REMIND = 'remind';
    public const FLAG_WIRE = 'wire';
    public const FLAG_VIEW = 'view';
    public const FLAG_VOTE = 'vote';

    public function __construct()
    {
        $this->addRole(new AdminRole());
        $this->addRole(new BannedRole());
        $this->addRole(new ChannelAdminRole());
        $this->addRole(new ChannelModeratorRole());
        $this->addRole(new ChannelNonSubscriberRole());
        $this->addRole(new ChannelOwnerRole());
        $this->addRole(new ChannelSubscriberRole());
        $this->addRole(new GroupAdminRole());
        $this->addRole(new GroupModeratorRole());
        $this->addRole(new GroupNonSubscriberRole());
        $this->addRole(new GroupOwnerRole());
        $this->addRole(new GroupSubscriberRole());
    }
}
