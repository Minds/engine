<?php
/**
 * Exception to be thrown when a feature is deprecated.
 */
namespace Minds\Exceptions;

class DeprecatedException extends \Exception
{
    /** @var string */
    protected $message = "This feature is deprecated";

    /** @var int */
    protected $code = 410;
}
