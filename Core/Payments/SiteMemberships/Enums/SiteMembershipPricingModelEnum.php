<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Enums;

enum SiteMembershipPricingModelEnum: string
{
    case RECURRING = 'recurring';
    case ONE_TIME = 'one_time';
}
