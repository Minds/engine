<?php
declare(strict_types=1);

namespace Minds\Core\Email\Confirmation\Exceptions;

use Minds\Exceptions\UserErrorException;

class EmailConfirmationMissingHeadersException extends UserErrorException
{
    /** @var int */
    protected $code = 400;

    /** @var string */
    protected $message = "Missing required parameters.";
}
