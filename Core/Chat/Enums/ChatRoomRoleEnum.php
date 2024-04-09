<?php
namespace Minds\Core\Chat\Enums;

enum ChatRoomRoleEnum
{
    /**
     * The user who created (and owns) the room
     */
    case OWNER;
    
    /**
     * A standard member role
     */
    case MEMBER;

}
