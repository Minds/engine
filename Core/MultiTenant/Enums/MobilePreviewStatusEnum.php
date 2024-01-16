<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Enums;

enum MobilePreviewStatusEnum: int
{
    case NO_PREVIEW = 0;
    case PENDING = 1;
    case READY = 2;
    case ERROR = 3;
}
