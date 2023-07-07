<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2\Exceptions;

use Minds\Exceptions\UserErrorException;

class InvalidPaymentMethodException extends UserErrorException
{
    protected $code = 400;
    protected $message = "The provided payment method is not supported.";
}
