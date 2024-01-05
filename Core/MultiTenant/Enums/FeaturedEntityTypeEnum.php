<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Enums;

/**
 * Enum for featured entity types.
 */
enum FeaturedEntityTypeEnum: string
{
    case USER = 'user';
    case GROUP = 'group';
}
