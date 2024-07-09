<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Events\Sockets\Enums;

enum ChatEventTypeEnum
{
    case NEW_MESSAGE;
    case MESSAGE_DELETED;
}
