<?php

namespace Minds\Core\MultiTenant\Enums;

enum TenantInviteEmailStatusEnum: int
{
    case PENDING = 1;
    case SENT = 2;
    case FAILED = 3;
    case CANCELLED = 4;
}
