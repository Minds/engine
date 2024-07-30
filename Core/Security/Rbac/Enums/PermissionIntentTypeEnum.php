<?php
declare(strict_types=1);

namespace Minds\Core\Security\Rbac\Enums;

/**
 * Enum for different permission intents.
 */
enum PermissionIntentTypeEnum: int
{
    case HIDE = 1;
    case WARNING_MESSAGE = 2;
    case UPGRADE = 3;
}
