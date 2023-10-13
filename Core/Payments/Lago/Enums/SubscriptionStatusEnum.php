<?php

namespace Minds\Core\Payments\Lago\Enums;

enum SubscriptionStatusEnum: string
{
    case PENDING = "pending";
    case CANCELED = "canceled";
    case TERMINATED = "terminated";
    case ACTIVE = "active";
}
