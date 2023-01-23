<?php
declare(strict_types=1);

namespace Minds\Core\Verification\Exceptions;

use Minds\Exceptions\UserErrorException;

class VerificationRequestNotFoundException extends UserErrorException
{
    protected $code = 400;
    protected $message = "";
}
