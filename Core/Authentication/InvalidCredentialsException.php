<?php
namespace Minds\Core\Authentication;

use Minds\Exceptions\UserErrorException;

class InvalidCredentialsException extends UserErrorException
{
    /** @var int */
    protected $code = 401;

    /** @var string */
    protected $message = "The provided credentials were invalid.";
}
