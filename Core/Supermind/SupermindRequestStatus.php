<?php

namespace Minds\Core\Supermind;

class SupermindRequestStatus
{
    const CREATED = 0;
    const ACCEPTED = 1;
    const REVOKED = 2;
    const REJECTED = 3;
    const FAILED_PAYMENT = 4;
    const EXPIRED = 5;
}
