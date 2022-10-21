<?php

declare(strict_types=1);

namespace Minds\Core\Authentication\Exceptions;

use Minds\Exceptions\UserErrorException;

class AuthenticationAttemptsExceededException extends UserErrorException
{
    protected $code = 429;
    protected $message = "LoginException::AttemptsExceeded";
}
