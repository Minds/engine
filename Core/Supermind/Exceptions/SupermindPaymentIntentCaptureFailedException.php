<?php

namespace Minds\Core\Supermind\Exceptions;

use Minds\Exceptions\UserErrorException;

/**
 *
 */
class SupermindPaymentIntentCaptureFailedException extends UserErrorException
{
    protected $code = 500;
    protected $message = "Failed to setup payment";
}
