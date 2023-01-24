<?php
declare(strict_types=1);

namespace Minds\Core\Verification\Models;

class VerificationRequestStatus
{
    public const PENDING = 1;
    public const VERIFIED = 2;
    public const FAILED = 3;
    public const EXPIRED = 4;
}
