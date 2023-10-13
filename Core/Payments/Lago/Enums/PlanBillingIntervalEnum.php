<?php

namespace Minds\Core\Payments\Lago\Enums;

enum PlanBillingIntervalEnum: string
{
    case YEARLY = "yearly";
    case QUARTERLY = "quarterly";
    case MONTHLY = "monthly";
    case WEEKLY = "weekly";
}
