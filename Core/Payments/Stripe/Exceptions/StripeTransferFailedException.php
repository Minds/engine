<?php

namespace Minds\Core\Payments\Stripe\Exceptions;

use Minds\Exceptions\ServerErrorException;

class StripeTransferFailedException extends ServerErrorException
{
    protected $message = "There was an issue while transferring your funds";
}
