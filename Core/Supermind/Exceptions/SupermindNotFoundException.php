<?php

namespace Minds\Core\Supermind\Exceptions;

class SupermindNotFoundException extends \Minds\Exceptions\UserErrorException
{
    protected $code = 404;

    protected $message = "The requested Supermind was not found.";
}
