<?php
namespace Minds\Core\Security\TwoFactor;

use Minds\Exceptions\UserErrorException;

class TwoFactorRequiredException extends UserErrorException
{
    /** @var int */
    protected $code = 401;

    /** @var string */
    protected $message = "TwoFactor is required.";
}
