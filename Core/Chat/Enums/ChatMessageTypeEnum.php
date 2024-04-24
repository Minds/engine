<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Enums;

enum ChatMessageTypeEnum
{
    case TEXT;
    case IMAGE;
    case VIDEO;
    case AUDIO;
    case RICH_EMBED;
}
