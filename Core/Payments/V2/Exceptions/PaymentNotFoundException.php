<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2\Exceptions;

use Minds\Exceptions\UserErrorException;

class PaymentNotFoundException extends UserErrorException
{
    protected $code = 404;
    protected $message = "The requested payment could not be found";
}
