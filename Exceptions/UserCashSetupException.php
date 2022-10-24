<?php

namespace Minds\Exceptions;

use Minds\Core\Router\Exceptions\ForbiddenException;

class UserCashSetupException extends ForbiddenException
{
    protected $message = "You must complete setting up your wallet cash details to proceed.";
}
