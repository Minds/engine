<?php

namespace Minds\Core\Email\Invites\Enums;

enum InviteEmailStatusEnum: int
{
    case PENDING = 1;
    case SENDING = 2;
    case SENT = 3;
    case FAILED = 4;
    case CANCELLED = 5;
    case ACCEPTED = 6;
}
