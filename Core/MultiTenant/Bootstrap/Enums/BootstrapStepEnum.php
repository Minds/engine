<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Enums;

/**
 * Enum for the Bootstrap steps.
 */
enum BootstrapStepEnum: string
{
    case TENANT_CONFIG_STEP = 'tenant_config';
    case LOGO_STEP = 'logos';
    case CONTENT_STEP = 'content';
    case FINISHED = 'finished';
}
