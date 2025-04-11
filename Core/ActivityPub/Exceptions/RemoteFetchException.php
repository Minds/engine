<?php
namespace Minds\Core\ActivityPub\Exceptions;

use Minds\Exceptions\ServerErrorException;

class RemoteFetchException extends ServerErrorException
{
    protected $code = 500;
    protected $message = "The remote server was unable to be fetched";
}
