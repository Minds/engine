<?php

namespace Minds\Core\Supermind\Exceptions;

use Minds\Exceptions\UserErrorException;

class SupermindRequestCreationCompletionException extends UserErrorException
{
    protected $code = 500;

    protected $message = "An error occurred whilst completing the creation of the Supermind request";
}
