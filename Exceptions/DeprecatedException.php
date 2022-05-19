<?php

namespace Minds\Exceptions;

use Minds\Exceptions\ServerErrorException;

/**
 * Exception to be thrown when functionality is deprecated.
 */
class DeprecatedException extends ServerErrorException
{
    /** @var int - http code */
    protected $code = 410;

    /** @var string - exception message */
    protected $message = "Deprecated feature";
}
