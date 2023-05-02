<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2\Enums;

class PaymentStatus
{
    public const NOT_APPLICABLE = null;
    public const PENDING = 1;
    public const COMPLETED = 2;
    public const CANCELLED = 3;
    public const REFUNDED = 4;
}
