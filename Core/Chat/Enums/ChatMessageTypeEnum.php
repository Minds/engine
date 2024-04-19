<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Enums;

enum ChatMessageTypeEnum: int
{
    case PLAIN_TEXT = 1;
    case IMAGE = 2;
    case VIDEO = 3;
    case AUDIO = 4;
    case RICH_EMBED = 5;
}
