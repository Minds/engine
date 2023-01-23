<?php
declare(strict_types=1);

namespace Minds\Core\Verification\Exceptions;

class VerificationRequestDeviceTypeNotFoundException extends \Minds\Exceptions\UserErrorException
{
    protected $code = 400;
    protected $message = "";
}
