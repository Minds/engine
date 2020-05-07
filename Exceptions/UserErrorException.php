<?php
/**
 * Exceptions that can be rendered to the user in a
 * safe way
 */
namespace Minds\Exceptions;

class UserErrorException extends \Exception
{
    /** @var string */
    protected $message = "An unknown error occurred";
}
