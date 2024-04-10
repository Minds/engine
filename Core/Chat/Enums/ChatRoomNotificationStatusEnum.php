<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Enums;

enum ChatRoomNotificationStatusEnum: string
{
    case MUTED = 'MUTED';
    case MENTIONS = 'MENTIONS';
    case ALL = 'ALL';
}
