<?php

namespace Minds\Core\Supermind\Exceptions;

use Minds\Exceptions\UserErrorException;

/**
 *
 */
class SupermindOffchainPaymentFailedException extends UserErrorException
{
    protected $code = 500;
    protected $message = "Failed to process offchain payment";
}
