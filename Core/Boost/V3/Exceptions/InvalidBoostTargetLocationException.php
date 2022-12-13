<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Exceptions;

use Minds\Exceptions\UserErrorException;

class InvalidBoostTargetLocationException extends UserErrorException
{
    protected $code = 400;
    protected $message = "";
}
