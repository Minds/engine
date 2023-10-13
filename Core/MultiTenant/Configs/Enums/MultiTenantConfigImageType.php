<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs\Enums;

enum MultiTenantConfigImageType: string
{
    case SQUARE_LOGO = 'square_logo';
    case HORIZONTAL_LOGO = 'horizontal_logo';
    case FAVICON = 'favicon';
}
