<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Exceptions;

use Minds\Exceptions\UserErrorException;

class NoMobileConfigFoundException extends UserErrorException
{
    protected $message = 'No mobile config found';
    protected $code = 404;
}
