<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Enums;

class BoostStatus
{
    public const PENDING = 1;
    public const APPROVED = 2;
    public const REJECTED = 3;
    public const REFUND_IN_PROGRESS = 4;
    public const REFUND_PROCESSED = 5;
    public const FAILED = 6;
    public const REPORTED = 7;
    public const PENDING_ONCHAIN_CONFIRMATION = 8;
    public const COMPLETED = 9;
}
