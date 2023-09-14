<?php
namespace Minds\Core\ActivityPub\Exceptions;

use Minds\Exceptions\ServerErrorException;

class NotImplementedException extends ServerErrorException
{
    protected $message = "The server is unable to process your request";
    protected $code = 501;
}
