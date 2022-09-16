<?php

namespace Minds\Core\Supermind\Exceptions;

use Minds\Exceptions\UserErrorException;

/**
 *
 */
class SupermindRequestStatusUpdateException extends UserErrorException
{
    protected $code = 500;

    protected $message = "An error occurred whilst updating the Supermind request status";
}
