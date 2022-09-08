<?php

namespace Minds\Core\Supermind\Exceptions;

use Minds\Exceptions\UserErrorException;

class SupermindRequestDeleteException extends UserErrorException
{
    protected $code = 500;

    protected $message = "An error occurred whilst deleting the Supermind request";
}
