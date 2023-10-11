<?php
namespace Minds\Exceptions;

use Exception;

/**
 * The ObsoleteCodeException can be thrown when a deprecated code path has been removed
 */
class ObsoleteCodeException extends Exception
{
    protected $code = 410;
    protected $message = "The code path has been removed and should not be referenced.";
}
