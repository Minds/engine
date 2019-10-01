<?php
namespace Minds\Core\Permissions\Roles;

use ReflectionClass;

class Flags
{
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

    final public static function toArray() : array
    {
        return (new ReflectionClass(static::class))->getConstants();
    }
}
