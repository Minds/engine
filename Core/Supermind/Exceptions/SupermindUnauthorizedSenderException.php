<?php

namespace Minds\Core\Supermind\Exceptions;

class SupermindUnauthorizedSenderException extends \Minds\Exceptions\UserErrorException
{
    protected $code = 403;

    protected $message = "You are not authorized to perform this action";
}
