<?php

namespace Minds\Core\MultiTenant\Enums;

enum TenantInviteStatusEnum: int
{
    case PENDING = 1;
    case SENT = 2;
    case PARTIAL_FAILURE = 3;
}
