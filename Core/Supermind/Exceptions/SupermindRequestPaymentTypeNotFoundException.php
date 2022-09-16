<?php

namespace Minds\Core\Supermind\Exceptions;

use Minds\Exceptions\UserErrorException;

/**
 *
 */
class SupermindRequestPaymentTypeNotFoundException extends UserErrorException
{
    protected $code = 400;

    protected $message = "The provided payment type is not supported by Supermind requests.";
}
