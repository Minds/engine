<?php
declare(strict_types=1);

namespace Minds\Core\Email\Confirmation\Exceptions;

use Minds\Exceptions\UserErrorException;

class EmailConfirmationInvalidCodeException extends UserErrorException
{
    /** @var int */
    protected $code = 401;

    /** @var string */
    protected $message = "Incorrect email confirmation code.";
}
