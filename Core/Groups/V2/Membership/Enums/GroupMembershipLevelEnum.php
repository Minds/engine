<?php
namespace Minds\Core\Groups\V2\Membership\Enums;

enum GroupMembershipLevelEnum: int
{
    // The user has been banned from the group
    case BANNED = -1;

    // The user has requested to join the group, but is not currently a member
    case REQUESTED = 0;

    // A regular member
    case MEMBER = 1;

    // A moderator, can approve posts etc
    case MODERATOR = 2;

    // The group owner (admin)
    case OWNER = 3;
}
