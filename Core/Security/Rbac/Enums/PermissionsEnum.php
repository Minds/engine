<?php
namespace Minds\Core\Security\Rbac\Enums;

enum PermissionsEnum
{
    case CAN_CREATE_POST;
    case CAN_UPLOAD_VIDEO;
    case CAN_UPLOAD_AUDIO;
    case CAN_COMMENT;
    case CAN_INTERACT;
    case CAN_CREATE_GROUP;
    case CAN_BOOST;
    case CAN_USE_RSS_SYNC;
    case CAN_ASSIGN_PERMISSIONS;
    case CAN_MODERATE_CONTENT;
    case CAN_CREATE_PAYWALL;
    case CAN_CREATE_CHAT_ROOM;
    case CAN_UPLOAD_CHAT_MEDIA;
}
