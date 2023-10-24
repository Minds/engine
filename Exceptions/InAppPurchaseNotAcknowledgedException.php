<?php
declare(strict_types=1);

namespace Minds\Exceptions;

class InAppPurchaseNotAcknowledgedException extends ServerErrorException
{
    protected $code = 400;
    protected $message = 'Payment unsuccessful. Please try again.';
}
