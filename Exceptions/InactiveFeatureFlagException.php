<?php

namespace Minds\Exceptions;

class InactiveFeatureFlagException extends UserErrorException
{
    protected $code = 403;
    protected $message = "The requested feature is not currently active";
}
