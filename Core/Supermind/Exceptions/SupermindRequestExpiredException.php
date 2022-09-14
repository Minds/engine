<?php

namespace Minds\Core\Supermind\Exceptions;

use Minds\Exceptions\UserErrorException;

class SupermindRequestExpiredException extends UserErrorException
{
    protected $code = 410;

    protected $message = "The Supermind request is expired";
}
