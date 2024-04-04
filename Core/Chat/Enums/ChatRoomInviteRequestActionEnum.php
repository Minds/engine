<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Enums;

enum ChatRoomInviteRequestActionEnum
{
    case ACCEPT;
    case REJECT;
    case REJECT_AND_BLOCK;
}
