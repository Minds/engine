<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Enums;

/**
 * Enum for specifying the return format of Jina responses.
 */
enum JinaReturnFormat: string
{
    /** Whole page - past visible viewport. */
    case PAGESHOT = 'pageshot';
 
    /** The initial landing viewport. */
    case SCREENSHOT = 'screenshot';
}
