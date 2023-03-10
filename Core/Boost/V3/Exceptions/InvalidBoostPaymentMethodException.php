<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Exceptions;

class InvalidBoostPaymentMethodException extends \Minds\Exceptions\UserErrorException
{
    protected $code = 400;
    protected $message = "";
}
