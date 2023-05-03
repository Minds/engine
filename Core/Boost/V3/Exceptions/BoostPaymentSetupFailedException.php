<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Exceptions;

class BoostPaymentSetupFailedException extends \Minds\Exceptions\ServerErrorException
{
    protected $code = 500;
    protected $message = "There was an error processing the payment";
}
