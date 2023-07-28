<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Exceptions;

use Minds\Exceptions\UserErrorException;

class GiftCardInsufficientFundsException extends UserErrorException
{
    protected $code = 400;
    protected $message = 'Insufficient credits to complete this transaction.';
}
