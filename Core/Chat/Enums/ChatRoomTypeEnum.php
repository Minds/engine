<?php
namespace Minds\Core\Chat\Enums;

enum ChatRoomTypeEnum
{
    /**
     *  A one to one chat will have 2 members only
     */
    case ONE_TO_ONE;
    
    /**
     * A room that has multiple users
     */
    case MULTI_USER;

    /**
     * A room that is owned by a group and has the group members as its owner
     */
    case GROUP_OWNED;
}
