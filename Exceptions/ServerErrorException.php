<?php
/**
 * Exceptions that can be rendered to the user in a
 * safe way
 */
namespace Minds\Exceptions;

class ServerErrorException extends \Exception
{
    /** @var string */
    protected $message = "An unknown server error has occurred";

    /** @var int */
    protected $code = 500;
}
