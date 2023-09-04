<?php

declare(strict_types=1);

namespace Minds\Core\Supermind;

/**
 *
 */
enum SupermindRequestStatus: int
{
    case PENDING = 0;
    case CREATED = 1;
    case ACCEPTED = 2;
    case REVOKED = 3;
    case REJECTED = 4;
    case FAILED_PAYMENT = 5;
    case FAILED = 6;
    case EXPIRED = 7;
    case TRANSFER_FAILED = 8;
    case EXPIRING_IN_PROGRESS = 9;
}
