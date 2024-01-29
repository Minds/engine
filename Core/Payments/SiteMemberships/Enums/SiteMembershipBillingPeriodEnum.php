<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Enums;

enum SiteMembershipBillingPeriodEnum: string
{
    case MONTHLY = "monthly";
    case YEARLY = "yearly";
}
