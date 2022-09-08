<?php

namespace Minds\Core\SMS\Exceptions;

use Minds\Exceptions\UserErrorException;

/**
 * Exception to be thrown when a region is unsupported.
 */
class UnsupportedRegionException extends UserErrorException
{
    protected $message = 'We are unable to offer support for your region currently';
}
