<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Enums;

enum MobileConfigImageTypeEnum: string
{
    case ICON = 'icon';
    case SPLASH = 'splash';
    case SQUARE_LOGO = 'square_logo';
    case HORIZONTAL_LOGO = 'horizontal_logo';
}
