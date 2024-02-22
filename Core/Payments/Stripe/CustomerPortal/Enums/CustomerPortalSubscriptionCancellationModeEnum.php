<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\CustomerPortal\Enums;

enum CustomerPortalSubscriptionCancellationModeEnum: string
{
    case IMMEDIATELY = 'immediately';
    case AT_PERIOD_END = 'at_period_end';
}
