<?php
namespace Minds\Core\Security\Rbac\Enums;

enum PermissionsEnum
{
    case CAN_CREATE_POST;
    case CAN_UPLOAD_VIDEO;
    case CAN_COMMENT;
    case CAN_INTERACT;
    case CAN_CREATE_GROUP;
    case CAN_BOOST;
    case CAN_ASSIGN_PERMISSIONS;
}
