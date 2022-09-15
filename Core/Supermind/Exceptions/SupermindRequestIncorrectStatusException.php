<?php

namespace Minds\Core\Supermind\Exceptions;

use Minds\Exceptions\UserErrorException;

class SupermindRequestIncorrectStatusException extends UserErrorException
{
    protected $code = 410;

    protected $message = "The Supermind request is not in the correct status to perform the requested action";
}
