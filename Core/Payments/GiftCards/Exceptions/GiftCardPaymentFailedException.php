<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Exceptions;

use Minds\Exceptions\ServerErrorException;

class GiftCardPaymentFailedException extends ServerErrorException
{
    protected $code = 500;
    protected $message = 'Gift card payment failed';
}
