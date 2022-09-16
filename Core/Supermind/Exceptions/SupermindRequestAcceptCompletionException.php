<?php

namespace Minds\Core\Supermind\Exceptions;

use Minds\Exceptions\UserErrorException;

/**
 *
 */
class SupermindRequestAcceptCompletionException extends UserErrorException
{
    protected $code = 500;

    protected $message = "An error occurred whilst accepting the Supermind request";
}
