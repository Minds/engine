<?php

namespace Minds\Core\Verification\Exceptions;

use Minds\Exceptions\UserErrorException;

class VerificationCodeMismatchException extends UserErrorException
{
    protected $code = 400;
    protected $message = "The provided code does not match what is on record.";
}
