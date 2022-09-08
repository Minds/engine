<?php

declare(strict_types=1);

namespace Minds\Core\Supermind;

/**
 *
 */
class SupermindRequestStatus
{
    const PENDING = 0;
    const CREATED = 1;
    const ACCEPTED = 2;
    const REVOKED = 3;
    const REJECTED = 4;
    const FAILED_PAYMENT = 5;
    const FAILED = 6;
    const EXPIRED = 7;
}
