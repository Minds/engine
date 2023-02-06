<?php

namespace Minds\Core\Boost\V3\Exceptions;

use Minds\Exceptions\UserErrorException;

class BoostCashPaymentSetupFailedException extends UserErrorException
{
    protected $code = 400;
    protected $message = "An error occurred while processing the payment.";
}
