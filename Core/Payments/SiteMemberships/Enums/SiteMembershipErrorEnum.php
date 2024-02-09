<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Enums;

enum SiteMembershipErrorEnum
{
    case SUBSCRIPTION_ALREADY_CANCELLED;
    case SUBSCRIPTION_ALREADY_EXISTS;
}
