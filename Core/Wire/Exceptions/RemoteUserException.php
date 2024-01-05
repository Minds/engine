<?php
declare(strict_types=1);

namespace Minds\Core\Wire\Exceptions;

use Minds\Exceptions\UserErrorException;

/**
 * Exception thrown when trying to make a payment to a remote user.
 */
class RemoteUserException extends UserErrorException
{
    protected $code = 400;
    protected $message = 'This channel cannot receive payments';
}
