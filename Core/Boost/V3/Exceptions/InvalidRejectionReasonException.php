<?php

namespace Minds\Core\Boost\V3\Exceptions;

use Minds\Exceptions\UserErrorException;

class InvalidRejectionReasonException extends UserErrorException
{
    protected $code = 400;
    protected $message = "The provided rejection reason is not an approved/existing reason. Please select a valid reason";
}
