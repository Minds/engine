<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Enums;

enum ChatRoomMemberStatusEnum
{
    case ACTIVE;
    case LEFT;
    case INVITE_PENDING;
    case INVITE_REJECTED;
}
